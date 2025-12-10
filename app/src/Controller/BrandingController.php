<?php

namespace App\Controller;

use App\Form\BrandingFormType;
use App\Form\TemplateFormType;
use App\Service\AccountService;
use App\Service\BrandingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/branding')]
#[IsGranted('ROLE_USER')]
class BrandingController extends AbstractController
{
    public function __construct(
        private BrandingService $brandingService,
        private AccountService $accountService
    ) {
    }

    #[Route('/configure', name: 'app_branding_configure', methods: ['GET', 'POST'])]
    public function configure(Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        // Ensure user has an account
        $account = $user->getAccount();
        if (!$account) {
            $account = $this->accountService->createDefaultAccount($user);
        }

        if (!$this->brandingService->canConfigureBranding($account)) {
            $this->addFlash('error', 'branding.access_denied');
            return $this->redirectToRoute('app_subscription_manage');
        }

        $branding = $this->brandingService->getBrandingForAccount($account);
        
        $form = $this->createForm(BrandingFormType::class, $branding);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $logoFile = $form->get('logo')->getData();
            
            // Check color accessibility and add warnings if needed
            $accessibilityWarnings = $this->brandingService->validateColorAccessibility(
                $data->getPrimaryColor(),
                $data->getSecondaryColor()
            );
            
            foreach ($accessibilityWarnings as $warning) {
                $this->addFlash('warning', $warning);
            }
            
            $this->brandingService->configureBranding($account, [
                'primaryColor' => $data->getPrimaryColor(),
                'secondaryColor' => $data->getSecondaryColor(),
                'logoPosition' => $data->getLogoPosition(),
                'logoSize' => $data->getLogoSize(),
            ], $logoFile);

            $this->addFlash('success', 'branding.save.success');
            return $this->redirectToRoute('app_branding_configure');
        }

        $canConfigureTemplate = $this->brandingService->canConfigureTemplate($account);
        $templateForm = null;
        
        if ($canConfigureTemplate) {
            $templateForm = $this->createForm(TemplateFormType::class, $branding);
            $templateForm->handleRequest($request);
            
            if ($templateForm->isSubmitted() && $templateForm->isValid()) {
                try {
                    $templateContent = $templateForm->get('customTemplate')->getData() ?? '';
                    $this->brandingService->configureTemplate($account, $templateContent);
                    $this->addFlash('success', 'branding.template.save.success');
                    return $this->redirectToRoute('app_branding_configure');
                } catch (\InvalidArgumentException $e) {
                    $this->addFlash('error', $e->getMessage());
                } catch (\Exception $e) {
                    $this->addFlash('error', 'branding.template.save.error');
                }
            }
        }

        return $this->render('branding/configure.html.twig', [
            'account' => $account,
            'branding' => $branding,
            'form' => $form,
            'canConfigureTemplate' => $canConfigureTemplate,
            'templateForm' => $templateForm?->createView(),
        ]);
    }

    #[Route('/logo/remove', name: 'app_branding_remove_logo', methods: ['POST'])]
    public function removeLogo(Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        // Ensure user has an account
        $account = $user->getAccount();
        if (!$account) {
            $account = $this->accountService->createDefaultAccount($user);
        }

        if (!$this->isCsrfTokenValid('remove_logo', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $this->brandingService->removeLogo($account);
        $this->addFlash('success', 'branding.logo.remove.success');
        
        return $this->redirectToRoute('app_branding_configure');
    }

    #[Route('/template/save', name: 'app_branding_template_save', methods: ['POST'])]
    public function saveTemplate(Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        // Ensure user has an account
        $account = $user->getAccount();
        if (!$account) {
            $account = $this->accountService->createDefaultAccount($user);
        }

        if (!$this->brandingService->canConfigureTemplate($account)) {
            $this->addFlash('error', 'branding.template.access_denied');
            return $this->redirectToRoute('app_branding_configure');
        }

        $branding = $this->brandingService->getBrandingForAccount($account);
        $templateForm = $this->createForm(TemplateFormType::class, $branding);
        $templateForm->handleRequest($request);

        if ($templateForm->isSubmitted() && $templateForm->isValid()) {
            try {
                $templateContent = $templateForm->get('customTemplate')->getData() ?? '';
                $this->brandingService->configureTemplate($account, $templateContent);
                $this->addFlash('success', 'branding.template.save.success');
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'branding.template.save.error');
            }
        } else {
            $this->addFlash('error', 'branding.validation.error');
        }

        return $this->redirectToRoute('app_branding_configure');
    }

    #[Route('/template/reset', name: 'app_branding_template_reset', methods: ['POST'])]
    public function resetTemplate(Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        // Ensure user has an account
        $account = $user->getAccount();
        if (!$account) {
            $account = $this->accountService->createDefaultAccount($user);
        }

        if (!$this->brandingService->canConfigureTemplate($account)) {
            $this->addFlash('error', 'branding.template.access_denied');
            return $this->redirectToRoute('app_branding_configure');
        }

        if (!$this->isCsrfTokenValid('reset_template', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        try {
            $this->brandingService->configureTemplate($account, '');
            $this->addFlash('success', 'branding.template.reset.success');
        } catch (\Exception $e) {
            $this->addFlash('error', 'branding.template.reset.error');
        }

        return $this->redirectToRoute('app_branding_configure');
    }

    #[Route('/reset', name: 'app_branding_reset', methods: ['POST'])]
    public function reset(Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        // Ensure user has an account
        $account = $user->getAccount();
        if (!$account) {
            $account = $this->accountService->createDefaultAccount($user);
        }

        if (!$this->brandingService->canConfigureBranding($account)) {
            $this->addFlash('error', 'branding.access_denied');
            return $this->redirectToRoute('app_subscription_manage');
        }

        if (!$this->isCsrfTokenValid('reset_branding', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        try {
            $this->brandingService->resetBranding($account);
            $this->addFlash('success', 'branding.reset.success');
        } catch (\Exception $e) {
            $this->addFlash('error', 'branding.reset.error');
        }

        return $this->redirectToRoute('app_branding_configure');
    }
}

