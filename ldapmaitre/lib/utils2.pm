#!/usr/bin/perl
# 10 Sept 2003 Debug pb du a database monitor
# Positionne $rootpw $rootdn $suffix de maniere a lire le compte root en dehors du code
# A adapter si config en LDAP et non fichier texte

use strict;
use Data::Dumper;
our $rootpw;
our $rootdn;
our $suffix;
our $masterhost='127.0.0.1';
our $SUFFIXEBASE;
our %secrets;
my $SlapdConfigFile='/etc/ldap/slapd.conf';
# ! Le mot de passe du rootdn de la base doit être en clair.. !#


sub LitLdapConfig{
   my $trouve=0;
   open CONFIG,"<$SlapdConfigFile" or die "Impossible de lire $SlapdConfigFile";
   while (<CONFIG>) {
		if (/database\s+.db/i){$trouve=1;next;}      
      if (/^suffix\s+"(.*)"/i && !(/delta-sync/i) && $trouve) {$suffix=$1;next;}
      if ($trouve && /^rootpw\s+(\S+)/i) { $rootpw=$1; next;}
      if ($trouve && /^rootdn\s+"(\S+)"/i) { $rootdn=$1; next;}
	   if ($trouve && /^database/i){last;}
   } # chaque ligne
   close CONFIG;
	$SUFFIXEBASE="ou=people,$suffix";
} 

1;
