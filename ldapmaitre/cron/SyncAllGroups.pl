#!/usr/bin/perl
use strict;
use Data::Dumper;
use Net::LDAPS;
use Getopt::Std;

my %maitre;
my %Hmail;
my $VERBOSE=0;
my $TEST=0;
our $masterhost;
our $suffix;
my $admin;  # mode admin
our $rootpw;
our $rootdn;
our $suffix;
use lib '/var/ldap/lib/';
use DBI;
require "utils2.pm";
my $modif=0;
my %HldapValide;
our ($opt_h, $opt_v,$opt_t);
my $filter='(&(amudatevalidation=*)(|(edupersonaffiliation=employee)(edupersonaffiliation=faculty)(edupersonaffiliation=researcher)(edupersonaffiliation=affiliate)))';
my $adminattr='amugroupadmin';	# DN des admins de groupe
my $memberattr='amugroupmember';	# DN des membres groupe forcés dans un groupe à filtre (gestion manuelle en LDAP)
my $filterattr='amugroupfilter';	# nom de l'attribut contennant un filtre LDAP ou SQl 
	
getopts('tvhf:d:');
if (defined($opt_h)){
   print "Usage: [-t] [-v]
 -v mode verbose\n  -t teste seulement\n";
   exit;
}
$TEST=1 if (defined($opt_t));
$VERBOSE=1 if (defined($opt_v));

	&LitLdapConfig(); # rempli rootdn rootpass suffix
	my $ldap = Net::LDAP->new($masterhost) or die "$@";
	my $mesg = $ldap->bind($rootdn, password => $rootpw, version => 3 );
	if ($mesg->code()) { print "Pb bind admin";exit -3;}

	$mesg= $ldap->search (  # perform a search
          base   => "ou=people,$suffix",
          filter => $filter,
          attrs => ['uid'],
          scope => 'one');
   print "Personnel:  ".$mesg->count."\n" if $VERBOSE;
   my @entries= $mesg->entries;
   foreach my $entr (@entries){
      my $uid=$entr->get_value('uid');
		$HldapValide{lc $uid}=1;
   }


	$mesg = $ldap->search (  # perform a search
			 base   => "ou=groups,$suffix",
			 filter => "($filterattr=*)",
			 attrs => ['cn',$filterattr,'member',$memberattr],
			 scope => 'one');
	print "Groupes: ".$mesg->count."\n" if $VERBOSE;
	my @entries= $mesg->entries;
	foreach my $entr (@entries){
		my %HGmember=();
		my %HGmemberManual=();
		my $groupeDN=$entr->dn;
		my $cn=$entr->get_value('cn');
		#print "groupe: $cn\n" if $VERBOSE;
		my $filter=$entr->get_value($filterattr);
		my @res=$entr->get_value('member');
		my @manual=$entr->get_value($memberattr);
		$HGmember{lc $_}=1 foreach @res;
		$HGmemberManual{lc $_}=1 foreach @manual;
		&Group($groupeDN,$filter,\%HGmember,\%HGmemberManual);
	}

sub Group {
	my ($groupeDN,$filter,$HGmember,$HGmemberManual)=@_;
	my %HldapDN;
	my @Tadd=();
	my @Tdelete=();
	my @Tchanges=();
	my $ADD=0;
	my $DELETE=0;
	my $base;
	my $GROUP=0;
	my @attrs;
	# patch de M.. pour peupler grouper-ent
	unless ($filter=~/^dbi:/){ #si LDAP
		if ($filter=~/$adminattr/i) {
			$base="ou=groups,$suffix";
			@attrs=($adminattr);
			$GROUP=1;
			#print "$GROUP\n";
		}
		else {
			$base="ou=people,$suffix";
			@attrs=('');
		}
		$mesg = $ldap->search (  # perform a search
				 base   => $base,
				 filter => $filter,
				 attrs => \@attrs,
				 scope => 'one');
		my @entries= $mesg->entries;
		#print "filter=$filter ".$mesg->count()."\n";
		foreach my $entr (@entries){
			if ($GROUP){
				my @admins=$entr->get_value($adminattr);
				#print "admins:@admins ".$entr->dn."\n";
				$HldapDN{lc $_}=1 foreach (@admins);
				#print "$_\n";
			}
			else {
				$HldapDN{lc $entr->dn}=1;
				#print $entr->dn."\n";
			}
		}
	}
	else { #SQL
		my ($dbi,$user,$pass,$sql)=split(/\|/,$filter);
		my $dbh = DBI->connect($dbi,$user,$pass) || do {warn( $DBI::errstr . "\n" );return};
		my $sthSearch = $dbh->prepare( $sql );
		$sthSearch->execute();
   	my  $login;
	   $sthSearch->bind_columns(\$login);
   	while($sthSearch->fetch){
         #print "\t$login\n" ;
			$login = lc $login;
			unless ($HldapValide{$login}){
				#print "$groupeDN $login plus actif\n" if $VERBOSE;
			}
			else {
				$HldapDN{"uid=$login,ou=people,$suffix"}=1;
			}
   	}
		$sthSearch->finish();
		$dbh->disconnect();
	}
	foreach (keys %{$HGmember}){
		#print "Gmember $_\n";
		unless (defined $HldapDN{$_}){
			unless ($$HGmemberManual{$_}){
				print "$groupeDN $_ delete\n" if $VERBOSE;
	      	push @Tdelete,$_;
		   	$DELETE=1;
			}
		}
	}
	foreach (keys %HldapDN){
		#print "ldapDN $_\n";
      unless (defined $HGmember->{$_}){
			print "$groupeDN $_ ajout\n" if $VERBOSE;
         push @Tadd,$_;
		   $ADD=1;
      }
 	}
	foreach (keys %{$HGmemberManual}){
      #print "ldapDN $_\n";
      unless (defined $HGmember->{$_}){
         print "$groupeDN $_ ajout\n" if $VERBOSE;
         push @Tadd,$_;
         $ADD=1;
      }
   }

	unless ($TEST){
	   if ($DELETE){
			push @Tchanges,'delete',['member',\@Tdelete];
		}
		if ($ADD){
			push @Tchanges,'add',['member',\@Tadd];
		}
		my $result = $ldap->modify($groupeDN, changes => \@Tchanges);
		$result->code && warn "failed to modify add $groupeDN: ", $result->error ;
		print "Modifications $groupeDN\n" if (($ADD or $DELETE) and $VERBOSE);
	}
}
