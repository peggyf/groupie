#!/usr/bin/perl
use strict;
use Data::Dumper;
use Net::LDAPS;
use Net::LDAP qw(LDAP_SUCCESS LDAP_PROTOCOL_ERROR);
my %maitre;
my %Hmail;
my $VERBOSE=0;
my $TEST=0;
our $masterhost;
my $admin;  # mode admin
our $rootpw;
our $rootdn;
our $suffix;
use lib '/var/ldap/lib/';
require "utils2.pm";
use Getopt::Std;
my $modif=0;
my $domain;
my %AD=();
my $groupattr='amugroup';


our ($opt_h, $opt_v,$opt_t,$opt_u,$opt_d);
getopts('tvhud:');
if (defined($opt_h)){
   print "Usage: [-u] [-t] [-v] [-d] salsa|lambada
 -v mode verbose\n  -t teste seulement -u mode incremental toutes les 10 minutes\n";
   exit;
}
$TEST=1 if (defined($opt_t));
$VERBOSE=1 if (defined($opt_v));
if (defined($opt_d)) {
   $domain=lc $opt_d;
}
if ($domain eq '') {
   print "Usage: [-u] [-t] [-v] [-d] salsa|lambada domaine manquant!\n";
   exit;
}

&GetAD();

&LitLdapConfig(); # rempli rootdn rootpass suffix
my $ldap = Net::LDAP->new($masterhost) or die "$@";
my $mesg = $ldap->bind($rootdn, password => $rootpw, version => 3 );
if ($mesg->code()) { print "Pb bind admin $!";exit -3;}

my $ldapAD = Net::LDAPS->new($AD{host}) or die "PB AD $@";
my $mesg = $ldapAD->bind($AD{admindn}, password => $AD{adminpass}, version => 3 );
if ($mesg->code()) { print "Pb bind admin AD $!";exit -3;}

$mesg = $ldap->search (  # perform a search
		 base   => "ou=groups,$suffix",
		 #filter => 'amuGroupAD=*',
		 filter => "objectclass=$groupattr",
		 attrs => ['cn','member','description'],
		 scope => 'one');
#print "entrées ".$mesg->count."\n" if $VERBOSE;
my @entries= $mesg->entries;
my %HGmember=();
foreach my $entr (@entries){
	%HGmember=();
	my $groupeDN=$entr->dn;
	my $cn=$entr->get_value('cn');
	my $desc=$entr->get_value('description');
	next if $cn=~/:preri:/;
	#next unless $cn eq 'amu:ufr:sciences:ldap:enseignants';
	#print "groupe: $cn\n" if $VERBOSE;
	my @res=$entr->get_value('member');
	foreach (@res) {
		#print "member: $_\n";
		if (/uid=(.*),ou=people,$suffix/i){
			my $uid=$1;
		#print "\t uid: $1\n";
			# pseudomember qu'on évite..
			next if $uid=~/groupie|gaiap|pedagiciel|geststmed|^preri$|siamu/;
			$HGmember{lc $uid}=1
		}
	}
	&Group($cn,\%HGmember,$desc);
}

sub Group {
	my ($group,$HGmember,$desc)=@_;
	my %HADmember=();
	my @Tadd=();
	my @Tdelete=();
	my @Tchanges=();
	my $ADD=0;
	my $DELETE=0;
	my $groupeDN;
	$group=~s/:/_/g;
	my $index=0;
	my $members=0;
	#return unless $group eq 'amu_svc_dosi_tous';
#http://search.cpan.org/dist/perl-ldap-0.54/lib/Net/LDAP/FAQ.pod#How_do_I_search_for_all_members_of_a_large_group_in_AD?
	while($index ne '*') {
		my $searchattr;
		($index > 0) ? ($searchattr="member;range=$index-*") : ($searchattr='member' );
		$mesg = $ldapAD->search (  # perform a search
				 base   =>"cn=$group,ou=groups,$AD{'base'}",
				 filter => 'objectclass=*',
				 attrs  => [$searchattr],
				 scope => 'base');
		if ($mesg->code == LDAP_SUCCESS) {
			my $entry = $mesg->entry(0);
			my $attr;
			# large group: let's do the range option dance
			if (($attr) = grep(/^member;range=/, $entry->attributes)) {
				  my @Tmember=$entry->get_value($attr);
				  foreach (@Tmember) {
						if (/cn=(.*),ou=people,$AD{'base'}/i){
				#			print "\t cn:$1\n";
							$HADmember{lc $1}=1;
							$members++;
						}
				  }
				  if ($attr =~ /^member;range=\d+-(.*)$/) {
					 $index = $1;
					 $index++  if ($index ne '*');
				  }
			}
			else { # small group: no need for the range dance
				  my @Tmember=$entry->get_value($searchattr);
				  foreach (@Tmember) {
			#			print "trace $_\n";
						if (/cn=(.*),ou=people,$AD{'base'}/i){
			#				print "\t cn:$1\n";
							$HADmember{lc $1}=1;
							$members++;
						}
				  }
				  last;
			}
		 }
		 else {last;}# failed
	} #while index
	if ($mesg->code != LDAP_SUCCESS) {
		if ( $mesg->error=~/NO_OBJECT/ && $mesg->count==0 ){
				return unless AddGroup($group,$desc);
		}
		else {
			print "$group: ".$mesg->error."\n";
			return;
		}
	}
	#return;
	#print "Groupe $group $members\n";
	my ($todelete,$toadd)=(0,0);
	my @lignes=();
	foreach (keys %{$HGmember}){
		#print "LDAPmember $_\n";
		unless (defined $HADmember{$_}){
			push @lignes, "\tajout $_\n" if $VERBOSE;
			print "$AD{'base'}\n";
			#next if /tarca/;
	      push @Tadd,"CN=$_,ou=People,$AD{'base'}";;
		   $ADD=1;
			$toadd++;
		}
	}
	foreach (keys %HADmember){
		#print "ADmember $_\n";
      unless (defined $HGmember->{$_}){
			push @lignes, "\tdelete $_\n" if $VERBOSE;
         push @Tdelete,"CN=$_,ou=People,$AD{'base'}";
		   $DELETE=1;
			$todelete++;
      }
 	}
	return unless ($ADD or $DELETE);
	print "Groupe $group $members Add $toadd Del $todelete\n" if ($ADD or $DELETE);
	print @lignes if $VERBOSE;
	unless ($TEST){
	   if ($DELETE){
			push @Tchanges,'delete',['member',\@Tdelete];
		}
		if ($ADD){
			push @Tchanges,'add',['member',\@Tadd];
		}
		my $groupeDN="CN=$group,OU=Groups,$AD{'base'}";
		my $result = $ldapAD->modify($groupeDN, changes => \@Tchanges);
		if ($result->code){
			print Dumper  @Tchanges;
			print "Pb ajout/suppression de membres $groupeDN: ", $result->code."\n" ;
			@Tchanges=();
			my @TaddValide=();
			$ADD=0;
			foreach (@Tadd){
			 	push @TaddValide,$_ if search ($_);
			}
			$ADD=1 if scalar @TaddValide;
			if ($DELETE){
				push @Tchanges,'delete',['member',\@Tdelete];
			}
			if ($ADD){
				push @Tchanges,'add',['member',\@TaddValide];
			}
			return unless ($ADD or $DELETE);
			$result = $ldapAD->modify($groupeDN, changes => \@Tchanges);
			if ($result->code){
				warn  "Pb ajout/suppression de membres rattrapage $group: ".$result->error."\n";
				print Dumper  @Tchanges;
			}
		}
		else {print "Modifications $groupeDN\n" if (($ADD or $DELETE) and $VERBOSE);}
	}
}

sub search{
	my ($uid)=@_;
     $mesg = $ldapAD->search (  # perform a search
             base   =>$uid,
             filter => 'objectclass=*',
             attrs  => [''],
             scope => 'base');
	unless ($mesg->count()){
		print "$uid non trouvé\n" if $VERBOSE;
		return 0;
	}
	return 1;
}

sub AddGroup{
	my ($group,$desc)=@_;
	my @attrs=();
	push @attrs,'objectClass',['top','group'];
	push @attrs,'cn',$group;
	push @attrs,'samaccountname',$group;
	push @attrs,'description',$desc if $desc;
	my $groupeDN="CN=$group,OU=Groups,$AD{'base'}";
	print "AJOUT $group\n" if $VERBOSE;
	unless ($TEST) {
		my $result = $ldapAD->add($groupeDN,attr => \@attrs);
		$result->code && warn "Peut pas créer le groupe $groupeDN: ", $result->error ;
	   print Dumper @attrs if $result->code;
		return 0;
	}
	else {
		print "Test Ajout $group\n",
	}
	return 1;
}

sub GetAD{
  open (HOSTS,'</var/ldap/etc/hosts') or die "Pas de fichier /var/ldap/etc/hosts a lire";
  while (<HOSTS>){
      chomp;

#      if ($domain eq 'lambada'){
#         s/salsa/lambada/gi;
#         s/jupiter/titan/gi;
#      }
      if (/^AD(\w+):(.*)$/){
        $AD{$1}=$2;
      }
  }
  close HOSTS;
}

