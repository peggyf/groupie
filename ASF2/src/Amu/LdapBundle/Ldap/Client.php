<?php
/**
 * Ce fichier fait partie du bundle LdapBundle
 *
 * @author Arnaud Salvucci <arnaud.salvucci@univ-amu.fr>
 */
namespace Amu\LdapBundle\Ldap;

/**
 * Classe qui simule un client ldap
 *
 * @author Arnaud Salvucci <arnaud.salvucci@univ-amu.fr>
 */
class Client
{
    /**
     *
     * @var resource l'identifiant de la connexion
     */
    public $link;
    
    /**
     *
     * @var array un array contenant tous les profils disponibles
     */
    protected $profils;
    
    /**
     *
     * @var array un array contenant la définition du profil utilisé
     */
    protected $profil;


    /**
     * Constructeur du service ldap
     *
     * @param array  $profils        un array contenant tous les profils disponibles
     * @param string $defaultProfils le nom du profil par défaut
     */
    public function __construct(array $profils, $defaultProfils)
    {
        $this->profils = $profils;
        $this->profil = $profils[$defaultProfils];
    }

    /**
     * Connection au serveur ldap
     *
     * @param string $profilName le nom du profil auquel on veut se connecter
     *
     * @return \Amu\LdapBundle\Ldap\Client
     * @throws \InvalidArgumentException
     */
    public function connect($profilName = '')
    {
        if ($profilName !== '') {
            if (!isset($this->profils[$profilName])) {
                throw new \InvalidArgumentException('Le profil n\'est pas défini');
            }
            $this->profil = $this->profils[$profilName];
        }

        $servers = [];
        foreach ($this->profil['servers'] as $server) {
            if ($server['ssl'] === true) {
                $servers[] = sprintf('ldaps://%s:%s', $server['host'], $server['port']);
            } else {
                $servers[] = sprintf('ldap://%s:%s', $server['host'], $server['port']);
            }
        }

        $connect = implode(' ', $servers);
        $this->link = @ldap_connect($connect);

        if (!$this->link) {
            throw new \Exception('['.ldap_errno($this->link).'] '.ldap_error($this->link));
        }

        $this->bind($relativeDN = $this->profil['relative_dn'], $this->profil['password']);

        return $this;
    }
    
    /**
     * Définition des options de la connexion
     *
     * @return boolean
     * @throws \Exception
     */
    private function setOption()
    {
        $arrayOptions = [
            LDAP_OPT_PROTOCOL_VERSION => $this->profil['protocol_version'],
            LDAP_OPT_NETWORK_TIMEOUT  => $this->profil['network_timeout']
        ];
                
        if (isset($this->profil['referrals'])) {
            $arrayOptions[LDAP_OPT_REFERRALS] = $this->profil['referrals'];
        }
        
        foreach ($arrayOptions as $key => $value) {
            if (@ldap_set_option($this->link, $key, $value)) {
                continue;
            } else {
                throw new \Exception('['.ldap_errno($this->link).'] '.ldap_error($this->link));
            }
        }

        return false;
    }
    
    /**
     * Execute le bind
     *
     * @param string $relativeDN la branche de connexion
     * @param string $password   le mot de passe
     * @return boolean|\Amu\LdapBundle\Ldap\Client
     * @throws \Exception
     */
    private function bind($relativeDN = null, $password = null)
    {
        $this->setOption();

        if (@ldap_bind($this->link, $relativeDN, $password)) {
            return $this;
        } else {
            throw new \Exception('['.ldap_errno($this->link).'] '.ldap_error($this->link));
        }
        return false;
    }
    
    /**
     * Recherche dans le ldap
     *
     * @param string  $baseDN     la base DN
     * @param string  $filter     le filtre de recherche
     * @param array   $attributes les attributs à retourner
     * @param boolean $normalize  flag qui indique si les données doivent être normalisées ou non. false par défaut.
     * @param integer $attrsonly  0 pour retourner les types et les valeurs des attributs
     * @param integer $sizelimit  limite le nombre d'entrées à récupérer
     * @param integer $timelimit  le temps d'execution max de la recherche
     * @param type $deref
     *
     * @return array un array contenant le résultat de la recherche
     * @throws \Exception
     */
    public function search(
        $baseDN = '',
        $filter = '',
        $attributes = array(),
        $normalize = false,
        $attrsonly = 0,
        $sizelimit = 0,
        $timelimit = 0,
        $deref = LDAP_DEREF_NEVER
    ) {
        $res = @ldap_search(
            $this->link,
            $baseDN,
            $filter,
            $attributes,
            $attrsonly,
            $sizelimit,
            $timelimit,
            $deref
        );

        if ($res) {
            $data = @ldap_get_entries($this->link, $res);

            if ($normalize) {
                return $this->normalizeData($data);
            } else {
                return $data;
            }
        } else {
            throw new \Exception('['.ldap_errno($this->link).'] '.ldap_error($this->link));
        }
    }
    
    /**
     * Ajout d'enregistrement dans le ldap
     *
     * @param string $dn   l'enregistrement à créer
     * @param array  $info les infos du nouvel enregistrement
     *
     * @return boolean
     * @throws Exception
     */
    public function add($dn, $info)
    {
        $res = @ldap_add($this->link, $dn, $info);
        
        if ($res) {
            return true;
        } else {
            throw new Exception('['.ldap_errno($this->link).'] '.ldap_error($this->link));
        }
        return false;
    }
    
    /**
     * Modification d'enregistrement dans le ldap
     *
     * @param string $dn   l'enregistrement à modifier
     * @param array  $info les nouvelles valeurs
     *
     * @return boolean
     * @throws Exception
     */
    public function modify($dn, $info)
    {
        $res = @ldap_modify($this->link, $dn, $info);
        
        if ($res) {
            return true;
        } else {
            throw new Exception('['.ldap_errno($this->link).'] '.ldap_error($this->link));
        }
        return false;
    }
    
    /**
     * Suppression d'enregistrement dans le ldap
     *
     * @param string $dn l'enregistrement à supprimer
     *
     * @return boolean
     * @throws \Exception
     */
    public function delete($dn)
    {
        $res = @ldap_delete($this->link, $dn);
        
        if ($res) {
            return true;
        } else {
            throw new \Exception('['.ldap_errno($this->link).'] '.ldap_error($this->link));
        }
        return false;
    }

    /**
     * Fermeture de la connexion
     */
    public function disconnect()
    {
        ldap_close($this->link);
    }
    
    /**
     * Accesseur de la propriété baseDN
     *
     * @return string la baseDN
     */
    public function getbaseDN()
    {
        return $this->profil['base_dn'];
    }
    
    /**
     * Destructeur
     */
    public function __destruct()
    {
        //$this->disconnect();
    }
    
    /**
     * Renvoi un Tableau épuré des données brutes $data passées en paramètres
     *
     * @param array $datas données brutes d'une intérrogation ldap
     * @return array Tableau épuré
     */
    private function normalizeData($data)
    {
        $arNormalized = array();
        if ($data['count'] > 0) {
            if ($data['count'] == 1) { // retour de données unique (une seule ligne)
                foreach ($data[0] as $key => $value) {
                    if ((!in_array($key, array("count", "dn"))) && (!is_numeric($key))) {
                        if ($value['count'] == 1) {
                            $arNormalized[$key] = $value[0];
                        } else {
                            $arValues = array();
                            for ($i = 0; $i < $value['count']; $i++) {
                                $arValues[] = $value[$i];
                            }
                            $arNormalized[$key] = $arValues;
                        }
                    }
                }
            } else { // retour de données multiples (plusieures lignes)
                for ($n = 0; $n < $data['count']; $n++) {
                    foreach ($data[$n] as $key => $value) {
                        if ((!in_array($key, array("count", "dn"))) && (!is_numeric($key))) {
                            if ($value['count'] == 1) {
                                $arNormalized[$n][$key] = $value[0];
                            } else {
                                $arValues = array();
                                for ($i = 0; $i < $value['count']; $i++) {
                                    $arValues[$n] = $value[$i];
                                }
                                $arNormalized[][$key] = $arValues;
                            }
                        }
                    }
                }
            }
        }

        return $arNormalized;
    }
}
