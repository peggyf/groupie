<?php

namespace Amu\RoleBundle\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Description of Network
 *
 * @author Michel UBÉDA <michel.ubeda@univ-amu.fr>
 */
class Networks
{
    private $intranetPlage;
    private $ipDev;
    private $loginDev;

    public function __construct(ContainerInterface $container)
    {
        $this->intranetPlage = array();
        $Plages = $container->getParameter("amu.roles.networks");
        foreach ($Plages as $onePlage) {
            $this->intranetPlage = array_merge($this->intranetPlage, $onePlage['plage']);
        }

        $this->ipDev = array();
        $this->loginDev = array();

        $DevPostes = $container->getParameter("amu.roles.developers");
        if (is_array($DevPostes)) {
            foreach ($DevPostes as $oneDevPoste) {
                $this->ipDev = array_merge(
                    $this->ipDev,
                    array($oneDevPoste["name"] => $oneDevPoste["ip"])
                );
                
                $this->loginDev = array_merge(
                    $this->loginDev,
                    array($oneDevPoste["name"] => $oneDevPoste["uid"])
                );
            }
        }

    }

    /**
     * Teste si une IP donnée fait partie des postes définis comme locaux
     * @param string $ip l'adresse IP à tester
     * @return bool
     */
    public function isLocal($ip = "")
    {
        return(($ip == "127.0.0.1"));
    }

    public function isDev2($login)
    {
        if ($login == "") {
            return (false);
        } else {
            return(in_array($login, $this->loginDev));
        }
    }

    public function isDev($ip)
    {
        if ($ip == "") {
            return (false);
        } else {
            return(in_array($ip, $this->ipDev));
        }
    }

    /**
     * Teste si l'adresse spécifié fait partie de l'un des réseaux de l'université
     * @param string $ip l'addresse ip du poste
     * @param bool $debug Afficher le mode debug (true/false defaut:false)
     * @return bool
     */
    public function isIntranet($ip = "", $debug = false)
    {
        $intra = false;
        if ($ip != "") {
            foreach ($this->intranetPlage as $onPlage) {
                $intra = $this->testIp($onPlage, $ip);
                if ($intra) {
                    break;
                }
            }
        }
        return($intra);
    }

    /**
     * Renvoi les informations/identité du développeur déclaré pour une adresse IP donnée
     * @param string $ip
     * @return string
     */
    public function devInfos($ip)
    {
        // renvoi la key de la valeur trouvé dans le Tableau
        return(array_search($ip, $this->ipDev));
    }

    /**
     * Renvoi les informations/identité du développeur déclaré pour une adresse IP donnée
     * @param string $ip
     * @return string
     */
    public function devInfos2($login)
    {
        // renvoi la key de la valeur trouvé dans le Tableau
        return(array_search($login, $this->loginDev));
    }

    
    private function getNetworksMask($calcul_mask)
    {
        $arMask = array(
            1 => "128.0.0.0",
            2 => "192.0.0.0",
            3 => "224.0.0.0",
            4 => "240.0.0.0",
            5 => "248.0.0.0",
            6 => "252.0.0.0",
            7 => "254.0.0.0",
            8 => "255.0.0.0",
            9 => "255.128.0.0",
            10 => "255.192.0.0",
            11 => "255.224.0.0",
            12 => "255.240.0.0",
            13 => "255.248.0.0",
            14 => "255.252.0.0",
            15 => "255.254.0.0",
            16 => "255.255.0.0",
            17 => "255.255.128.0",
            18 => "255.255.192.0",
            19 => "255.255.224.0",
            20 => "255.255.240.0",
            21 => "255.255.248.0",
            22 => "255.255.252.0",
            23 => "255.255.254.0",
            24 => "255.255.255.0",
            25 => "255.255.255.128",
            26 => "255.255.255.192",
            27 => "255.255.255.224",
            28 => "255.255.255.240",
            29 => "255.255.255.248",
            30 => "255.255.255.252",
            31 => "255.255.255.254",
            32 => "255.255.255.255"
        );

        $iValueMask = -1;
        $calcul_chaine_mask = "255.255.255.255";
        $iValueMask = intval($calcul_mask);
        if ($iValueMask > 0 and $iValueMask <= 32) {
            $calcul_chaine_mask = $arMask[$iValueMask];
        }
        
        return $calcul_chaine_mask;
        
    }
    /**
     * Fonction public de calcul/test/vérification d'appartenance à une plage de réseaux
     * @param array $plage la plage de réseaux défini comme intranet
     * @param string $ip l'adresse ip du poste
     * @return bool
     */
    public function testIp($plage, $ip)
    {
        if (strpos($plage, "/") !== false) {
            list($calcul_adresse_ip, $calcul_mask) = explode("/", $plage);
        } else {
            $calcul_chaine_mask = "255.255.255.255";
            $calcul_adresse_ip = $plage;
        }

        if (!empty($calcul_mask)) {
            // Validation du champs IP
            $calcul_inetaddr = ip2long($calcul_adresse_ip);
            $calcul_adresse_ip = long2ip($calcul_inetaddr);

            $calcul_chaine_mask=  $this->getNetworksMask($calcul_mask);

            // Calcul du nombre de HOST
            if ($calcul_mask == 32) {
                $calcul_host = 1;
            } else {
                $calcul_host = pow(2, 32 - $calcul_mask) - 2;
            }

            // Calcul de la route
            $calcul_route = $calcul_inetaddr & ip2long($calcul_chaine_mask); // Ajoute l'IP et le masque en binaire
            $calcul_route = long2ip($calcul_route); // Convertit l'adresse inetaddr en IP
            
            // Calcul de la premiere adresse
            if ($calcul_mask == 32) {
                $offset = 0;
            } else {
                $offset = 1;
            }

            if ($calcul_mask == 31) {
                $calcul_premiere_ip = "N/A";
            } else {
                $calcul_premiere_ip = ip2long($calcul_route) + $offset;
                $calcul_premiere_ip = long2ip($calcul_premiere_ip);
            }

            // Calcul de la dernière adresse
            if ($calcul_mask == 32) {
                $offset = -1;
            } else {
                $offset = 0;
            }
            if ($calcul_mask == 31) {
                $calcul_derniere_ip = "N/A";
            } else {
                $calcul_derniere_ip = ip2long($calcul_route) + $calcul_host + $offset;
                $calcul_derniere_ip = long2ip($calcul_derniere_ip);
            }
            // test de l'ip sur les adresses de la plage
            $ip_inf1 = 0;
            $ip_inf2 = 0;
            $ip_inf3 = 0;
            $ip_inf4 = 0;
            $ip_sup1 = 0;
            $ip_sup2 = 0;
            $ip_sup3 = 0;
            $ip_sup4 = 0;
            $ip1 = 0;
            $ip2 = 0;
            $ip3 = 0;
            $ip4 = 0;

            try {
                if (strpos($calcul_premiere_ip, '.') !== false) {
                    list($ip_inf1, $ip_inf2, $ip_inf3, $ip_inf4) = preg_split('/\./', $calcul_premiere_ip);
                }
                if (strpos($calcul_derniere_ip, '.') !== false) {
                    list($ip_sup1, $ip_sup2, $ip_sup3, $ip_sup4) = preg_split('/\./', $calcul_derniere_ip);
                }
                if (strpos($ip, '.') !== false) {
                    list($ip1, $ip2, $ip3, $ip4) = preg_split('/\./', $ip);
                }
            } catch (Exception $e) {
            }

            if ((($ip1 <= $ip_sup1) and ($ip1 >= $ip_inf1)) and (($ip2 <= $ip_sup2) and ($ip2 >= $ip_inf2)) and (($ip3 <= $ip_sup3) and ($ip3 >= $ip_inf3)) and (($ip4 <= $ip_sup4) and ($ip4 >= $ip_inf4))) {
                return true;
            } else {
                return false;
            };
        } else {
            if ($calcul_adresse_ip == $ip) {
                return true;
            } else {
                return false;
            };
        };
    }
}
