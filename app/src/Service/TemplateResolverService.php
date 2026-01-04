<?php

namespace App\Service;

use App\Entity\Account;
use App\Entity\AccountBranding;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;

class TemplateResolverService
{
    private ?ArrayLoader $arrayLoader = null;
    private bool $loaderInitialized = false;

    public function __construct(
        private BrandingService $brandingService,
        private Environment $twig
    ) {
    }

    /**
     * Resolve which template to use for rendering a public card page.
     * 
     * Returns the template name to use, or null to use default template.
     * For Enterprise accounts with custom templates, creates a dynamic template.
     */
    public function resolveTemplate(Account $account): ?string
    {
        $branding = $this->brandingService->getBrandingForAccount($account);
        
        if (!$branding || !$branding->getCustomTemplate()) {
            return null; // Use default template
        }

        // Enterprise account with custom template
        $customTemplateContent = $branding->getCustomTemplate();
        
        // Create a unique template name based on account ID
        $templateName = 'custom_card_template_' . $account->getId();
        
        // Register template in Twig loader using ArrayLoader
        $this->registerCustomTemplate($templateName, $customTemplateContent);
        
        return $templateName;
    }

    /**
     * Register a custom template in Twig's loader.
     */
    private function registerCustomTemplate(string $templateName, string $templateContent): void
    {
        // Initialize ArrayLoader and ChainLoader once per request
        if (!$this->loaderInitialized) {
            $originalLoader = $this->twig->getLoader();
            
            // Create ArrayLoader for custom templates
            $this->arrayLoader = new ArrayLoader();
            
            // Chain loaders: ArrayLoader first (for custom templates), then original loader
            if ($originalLoader instanceof ChainLoader) {
                // If already a ChainLoader, prepend our ArrayLoader
                $loaders = $originalLoader->getLoaders();
                array_unshift($loaders, $this->arrayLoader);
                $chainLoader = new ChainLoader($loaders);
            } else {
                // Create new ChainLoader with ArrayLoader and original loader
                $chainLoader = new ChainLoader([$this->arrayLoader, $originalLoader]);
            }
            
            $this->twig->setLoader($chainLoader);
            $this->loaderInitialized = true;
        }
        
        // Add template to ArrayLoader
        $this->arrayLoader->setTemplate($templateName, $templateContent);
    }

    /**
     * Validate that a custom template can be rendered without errors.
     */
    public function validateTemplateRender(Account $account, array $context = []): bool
    {
        try {
            $templateName = $this->resolveTemplate($account);
            
            if (!$templateName) {
                return true; // Default template is always valid
            }
            
            // Try to render the template with a test context
            $this->twig->render($templateName, $context);
            
            return true;
        } catch (SyntaxError $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}

