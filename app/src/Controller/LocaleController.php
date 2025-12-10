<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LocaleController extends AbstractController
{
    #[Route('/locale/{_locale}', name: 'app_change_locale', requirements: ['_locale' => 'en|fr'])]
    public function changeLocale(Request $request, string $_locale): Response
    {
        // Save locale to session
        $request->getSession()->set('_locale', $_locale);

        // Redirect to the previous page or home
        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_home');
    }
}

