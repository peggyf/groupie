<?php

namespace Amu\CasBundle\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bridge\Monolog\Logger;

class Cas
{
    private $trace = false;
    private $container = false;
    private $logger = null;

    public function __construct(ContainerInterface $container, Logger $logger)
    {
        $this->container = $container;
        $this->logger = $logger;
        $this->trace = false;
    //$this->trace = $this->container->getParameter('amu.cas.trace');
    $this->initCas();
    }

    private function Log($infos, $forceTrace = false)
    {
        if ($this->trace || $forceTrace) {
            $this->logger->addDebug("amu.cas [".date("d/m/Y à H:i:s")."] ==> ".$infos);
        }
    }

  /**
   * Initialisation/Chargement du Client CAS avec sa config
   */
  private function initCas()
  {
      $this->Log("initCas => Initialisation/Chargement du Client CAS avec sa config (amu.cas.config.xxx)...");

      \phpCAS::client(
            $this->container->getParameter('amu.cas.config.cas_protocol'),
            $this->container->getParameter('amu.cas.config.cas_server'),
            $this->container->getParameter('amu.cas.config.cas_port'),
            $this->container->getParameter('amu.cas.config.cas_path'),
            false
            ); // change Session ID (true/false)

    if ($this->container->getParameter('amu.cas.config.ca_cert_path') != "") {
        $this->Log("initCas => initialisation du Certificat : setCasServerCACert()...");
        \phpCAS::setCasServerCACert($this->container->getParameter('amu.cas.config.ca_cert_path'));
    } else {
        \phpCAS::setNoCasServerValidation();
    }
  }

  /**
   *   Force l'authentification CAS afficahge de la fenêtre de connexion si pas déjà authentifié
   *    (usage de phpCAS::forceAuthentication puis phpCAS::getUser() )
   *   et initialisation du context User/Symfony via les services
   *        amu_user_provider + amu.roles + amu.networks
   *
   * @return string identifiant de la personne authentifié CAS
   */
  public function login()
  {
      $this->Log("Tentative de connexion : forceAuthentication() et getUser()...");
      $login = "";
      if (\phpCAS::forceAuthentication()) {
          $login = \phpCAS::getUser();
          $this->Log("Connexion OK : $login");
      }

      return $login;
  }

  /**
   *  Vérification Authentification CAS et retour de l'identifiant si connecté
   *
   * @return string identifiant de la personne authentifié CAS ("" si non authentifié)
   */
  public function check()
  {
      $this->Log("Détection connexion antérieure (check): checkAuthentication() et getUser()...");
      $login = "";
      if (\phpCAS::checkAuthentication()) {
          $login = \phpCAS::getUser();
          $this->Log("Utilisateur authentifié CAS  : $login");
      } else {
          $this->Log("Utilisateur non authentifié CAS !");
      }

      return $login;
  }

  /**
   * Force la déconnexion de l'authentification CAS (redirection vers la fenêtre de déconnexion du cas)
   *
   * @return string ""
   */
  public function logout()
  {
      $this->Log("Tentative de déconnexion du CAS...");
      if (\phpCAS::isAuthenticated()) {
          \phpCAS::logout();
      }
      $this->Log("Déconnexion CAS Ok");

      return "";
  }
}
