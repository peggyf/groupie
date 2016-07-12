<?php

namespace Amu\CasBundle\Listener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

//use Symfony\Component\Security\Core\Exception\SessionUnavailableException;
//use Symfony\Component\Security\Core\Exception\CredentialsExpiredException;
//use Symfony\Component\Security\Core\Exception\AuthenticationException;


class TimeoutListener implements EventSubscriberInterface
{
    /**
   * @var Container
   */
    private $container;
  
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(Container $container, Logger $logger)
    {
        if (($container===null)||(!$container instanceof  Container)) {
            throw new \InvalidArgumentException('TimeoutListener : le listener doit avoir un "Symfony\Component\DependencyInjection\Container" injecté dans sa configuration');
        }
        $this->container = $container;
    
        if (($logger===null)||(!$logger instanceof Logger)) {
            throw new \InvalidArgumentException('TimeoutListener : le listener doit avoir un "Symfony\Bridge\Monolog\Logger" injecté dans sa configuration');
        }
        $this->logger = $logger;
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $debug=false;

//    $session =  $this->container->get('request_stack')->getCurrentRequest()->getSession();
        $session =  $event->getRequest()->getSession();
    
        if ($session /*&& $session->isStarted()*/) {
            $dtCreated = $session->getMetadataBag()->getCreated();
            $lifeTime = $session->getMetadataBag()->getLifetime();
            $dtUsed = $session->getMetadataBag()->getLastUsed();
            $maxIdleTime=$this->container->getParameter("amu.cas.timeout.idle");

            $message="";
            $forceLogout=false;

            if (($dtCreated) && (time() - $dtCreated > $lifeTime)) { // SESSION EXPIRÉ
                $message="La SESSION est arrivé à expiration ($lifeTime secondes)... ";
                $forceLogout=$this->container->getParameter("amu.cas.force_logout.on_session_timeout");
            } elseif (($dtUsed) && (time() - $dtUsed > $maxIdleTime)) { // TEMPS D'INACTIVITÉ MAXIMALE DÉPASSÉ
                $message="la durée d'INACTIVITÉ maximale ($maxIdleTime secondes) a été atteinte... ";
                $forceLogout=$this->container->getParameter("amu.cas.force_logout.on_idle_timeout");
            }
      
            if ($message!="") {
                $debugInfos="";
                if ($debug) {
                    $debugInfos="<hr><b>dtCreated</b>=$dtCreated (". date("d/m/Y H:i:s", $dtCreated)
                      ."<hr><b>dtUsed</b>=$dtUsed (". date("d/m/Y H:i:s", $dtUsed)
                      ."<hr><b>lifeTime</b>=$lifeTime (". date("H:i:s", $lifeTime)
                      ."<hr><b>maxIdleTime</b>=$maxIdleTime (". date("H:i:s", $maxIdleTime);
                }
        
                $message.=(($forceLogout)?"\nDéconexion CAS forcée":"");
                if ($debug) {
                    $this->logger->addWarning($message, array('session_getMetadataBag' => "<pre>" . print_r($session->getMetadataBag(), true) . "</pre>", 'debug_infos' => $debugInfos));
                }
                $this->razAuth($session, $message, $forceLogout);
            }
        }
    }

    /**
     * Efface les inforamtion d'authentification...
     * @param \Session $session
     * @param bool $forceLogout=false Force la déconexion du CAS (ssi=true)
     *
     */
    private function razAuth(Session $session, $message, $forceLogout=false)
    {
        $allowAnonymous=$this->container->getParameter("amu.cas.config.cas_allow_anonymous");
//     $this->container->loadFromExtension('security', array(
//    'firewalls' => array(
//        $providerKey => array('form_login' => array(
//            // ...
//            'default_target_path' => '/admin',
//        )),
//    ),
//));
    
    /*
     à faire detection de la config parent :
     
     firewalls:

        name_xy:

          ...
          anonymous: true <= à récupérer
          ...
          cas:

            cas_allow_anonymous: false
            cas_server: ident.univ-amu.fr
            cas_port: 443
            cas_path: /cas/
          ...

     */
    
//        // load firewall map
//        $firewalls =  $this->container->get('security.firewall');
//        foreach ($firewalls as $name => $firewall) {
//
//          if($name=="secured_area"){
//            print_r($firewall);
//            throw new \Exception("trouvé!!");;
//          }
//        }

        if ($tokenStorage = $this->container->get('security.token_storage')) {
            if ($token=$tokenStorage->getToken()) {
                if (!$token instanceof  AnonymousToken) {
                    if ($allowAnonymous) {
                        $providerKey=$token->getProviderKey();
                        $newToken = new AnonymousToken($providerKey, "anon.");
                        $tokenStorage->setToken($newToken);
                        $session->set('_security_'.$providerKey, serialize($newToken));
                        $session->save();
                    }
                }
            }
        }

        if ($forceLogout==true) {
            $session->clear();
            $this->container->get("amu.cas")->logout();
        } else {
            if ($allowAnonymous==false) {
                $this->container->get("amu.cas")->login();
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return array(KernelEvents::RESPONSE => array('onKernelResponse', -1000)); // après SessionListener
        //  KernelEvents::RESPONSE => array(array('onKernelResponse', -1000)), // SaveSessionListener
        //  KernelEvents::REQUEST => array('onKernelRequest', 128), // SessionListener
        //  KernelEvents::REQUEST => array('onKernelRequest', 192), // TestSessionListener
        //  KernelEvents::RESPONSE => array('onKernelResponse', -128), // TestSessionListener
    }
}
