<?php
namespace Amu\CasBundle\Security\Http\EntryPoint;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class CasAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    private $loginPath;
    private $useForward;
    private $httpKernel;
    private $httpUtils;

    /**
     * Constructor
     *
     * @param HttpKernelInterface $kernel
     * @param HttpUtils           $httpUtils  An HttpUtils instance
     * @param string              $loginPath  The path to the login form
     * @param Boolean             $useForward Whether to forward or redirect to the login form
     */
    public function __construct(HttpKernelInterface $kernel, HttpUtils $httpUtils, $loginPath, $useForward = false)
    {
        $this->httpKernel = $kernel;
        $this->httpUtils = $httpUtils;
        $this->loginPath = $loginPath;
        $this->useForward = (Boolean) $useForward;
    }

    /**
     * {@inheritdoc}
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        if ($this->useForward) {
            $subRequest = $this->httpUtils->createRequest($request, $this->loginPath);

            return $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        }

        return $this->httpUtils->createRedirectResponse($request, $this->loginPath);
    }
}
