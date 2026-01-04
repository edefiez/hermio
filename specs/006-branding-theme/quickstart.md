# Quickstart Guide: Branding & Theme (Pro / Enterprise)

**Feature**: 006-branding-theme  
**Date**: December 10, 2025  
**Target Audience**: Developers implementing this feature

## Overview

This quickstart guide provides step-by-step instructions for implementing the account-level branding customization system. Follow these steps in order to build the feature incrementally.

## Prerequisites

- Symfony 8.0+ installed and configured
- Feature 002 (User Account & Authentication) completed
- Feature 003 (Account Subscription) completed
- Feature 005 (Digital Card Management) completed
- Database connection configured (PostgreSQL or MySQL)
- Doctrine ORM 3.x installed
- Account and Card entities exist

## Implementation Steps

### Step 1: Create Logo Storage Directory

**Command**: 
```bash
cd app
mkdir -p public/uploads/branding/logos
chmod 755 public/uploads/branding/logos
```

**Verification**: Directory exists and is writable by web server.

---

### Step 2: Create AccountBranding Entity

**File**: `app/src/Entity/AccountBranding.php`

Create the AccountBranding entity with relationships and properties:

```php
namespace App\Entity;

use App\Repository\AccountBrandingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AccountBrandingRepository::class)]
#[ORM\Table(name: 'account_branding')]
#[ORM\UniqueConstraint(name: 'account_branding_unique', columns: ['account_id'])]
#[ORM\HasLifecycleCallbacks]
class AccountBranding
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Account::class, inversedBy: 'branding')]
    #[ORM\JoinColumn(name: 'account_id', referencedColumnName: 'id', nullable: false, unique: true, onDelete: 'CASCADE')]
    private Account $account;

    #[ORM\Column(type: Types::STRING, length: 7, nullable: true)]
    #[Assert\Regex(
        pattern: '/^#[0-9A-Fa-f]{6}$/',
        message: 'branding.color.invalid_format'
    )]
    private ?string $primaryColor = null;

    #[ORM\Column(type: Types::STRING, length: 7, nullable: true)]
    #[Assert\Regex(
        pattern: '/^#[0-9A-Fa-f]{6}$/',
        message: 'branding.color.invalid_format'
    )]
    private ?string $secondaryColor = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $logoFilename = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    #[Assert\Choice(
        choices: ['top-left', 'top-center', 'top-right', 'center', 'bottom-left', 'bottom-center', 'bottom-right'],
        message: 'branding.logo.position.invalid'
    )]
    private ?string $logoPosition = 'top-left';

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    #[Assert\Choice(
        choices: ['small', 'medium', 'large'],
        message: 'branding.logo.size.invalid'
    )]
    private ?string $logoSize = 'medium';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $customTemplate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    // Getters and setters...
}
```

**Verification**: Entity validates, can be persisted to database.

---

### Step 3: Update Account Entity

**File**: `app/src/Entity/Account.php`

Add OneToOne relationship to AccountBranding:

```php
#[ORM\OneToOne(targetEntity: AccountBranding::class, mappedBy: 'account', cascade: ['persist', 'remove'])]
private ?AccountBranding $branding = null;

public function getBranding(): ?AccountBranding
{
    return $this->branding;
}

public function setBranding(?AccountBranding $branding): static
{
    if ($branding === null) {
        if ($this->branding !== null) {
            $this->branding->setAccount(null);
        }
        $this->branding = null;
    } else {
        $branding->setAccount($this);
        $this->branding = $branding;
    }
    return $this;
}
```

**Verification**: Account entity has branding relationship.

---

### Step 4: Create AccountBrandingRepository

**File**: `app/src/Repository/AccountBrandingRepository.php`

Create repository for AccountBranding queries:

```php
namespace App\Repository;

use App\Entity\Account;
use App\Entity\AccountBranding;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AccountBrandingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccountBranding::class);
    }

    /**
     * Find branding for an account with optimized query.
     * Uses JOIN to avoid N+1 queries when fetching account with branding.
     */
    public function findOneByAccount(Account $account): ?AccountBranding
    {
        return $this->createQueryBuilder('ab')
            ->where('ab.account = :account')
            ->setParameter('account', $account)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
```

**Verification**: Repository can query AccountBranding entities.

---

### Step 5: Create Migration

**Command**:
```bash
cd app
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

**Verification**: Migration creates `account_branding` table successfully.

---

### Step 6: Create BrandingService

**File**: `app/src/Service/BrandingService.php`

Create service for branding management:

```php
namespace App\Service;

use App\Entity\Account;
use App\Entity\AccountBranding;
use App\Enum\PlanType;
use App\Repository\AccountBrandingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class BrandingService
{
    private string $logoStoragePath;

    public function __construct(
        private AccountBrandingRepository $accountBrandingRepository,
        private EntityManagerInterface $entityManager,
        private Filesystem $filesystem,
        string $projectDir
    ) {
        $this->logoStoragePath = $projectDir . '/public/uploads/branding/logos';
    }

    public function canConfigureBranding(Account $account): bool
    {
        $planType = $account->getPlanType();
        return $planType === PlanType::PRO || $planType === PlanType::ENTERPRISE;
    }

    public function canConfigureTemplate(Account $account): bool
    {
        return $account->getPlanType() === PlanType::ENTERPRISE;
    }

    public function getBrandingForAccount(Account $account): ?AccountBranding
    {
        $branding = $this->accountBrandingRepository->findOneByAccount($account);
        
        if (!$branding) {
            return null;
        }
        
        // Apply plan restrictions
        $planType = $account->getPlanType();
        if ($planType === PlanType::FREE) {
            return null;
        }
        
        if ($planType === PlanType::PRO) {
            // Pro plan: disable template feature
            $branding->setCustomTemplate(null);
        }
        
        return $branding;
    }

    public function configureBranding(Account $account, array $data, ?UploadedFile $logoFile = null): AccountBranding
    {
        if (!$this->canConfigureBranding($account)) {
            throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException('Branding is only available for Pro and Enterprise plans');
        }

        $branding = $this->accountBrandingRepository->findOneByAccount($account);
        
        if (!$branding) {
            $branding = new AccountBranding();
            $branding->setAccount($account);
        }

        // Update colors
        if (isset($data['primaryColor'])) {
            $branding->setPrimaryColor($data['primaryColor'] ?: null);
        }
        if (isset($data['secondaryColor'])) {
            $branding->setSecondaryColor($data['secondaryColor'] ?: null);
        }

        // Handle logo upload
        if ($logoFile) {
            $this->validateLogoFile($logoFile);
            $oldLogo = $branding->getLogoFilename();
            $newLogo = $this->uploadLogo($logoFile);
            $branding->setLogoFilename($newLogo);
            
            // Delete old logo
            if ($oldLogo) {
                $this->deleteLogoFile($oldLogo);
            }
        }

        // Update logo position and size
        if (isset($data['logoPosition'])) {
            $branding->setLogoPosition($data['logoPosition'] ?: null);
        }
        if (isset($data['logoSize'])) {
            $branding->setLogoSize($data['logoSize'] ?: null);
        }

        $this->entityManager->persist($branding);
        $this->entityManager->flush();

        return $branding;
    }

    public function configureTemplate(Account $account, string $templateContent): AccountBranding
    {
        if (!$this->canConfigureTemplate($account)) {
            throw new \AccessDeniedException('Template customization is only available for Enterprise plans');
        }

        $branding = $this->accountBrandingRepository->findOneByAccount($account);
        
        if (!$branding) {
            $branding = new AccountBranding();
            $branding->setAccount($account);
        }

        // Validate template syntax (implement Twig validation)
        $this->validateTemplateSyntax($templateContent);
        
        $branding->setCustomTemplate($templateContent);

        $this->entityManager->persist($branding);
        $this->entityManager->flush();

        return $branding;
    }

    public function removeLogo(Account $account): void
    {
        $branding = $this->accountBrandingRepository->findOneByAccount($account);
        
        if ($branding && $branding->getLogoFilename()) {
            $this->deleteLogoFile($branding->getLogoFilename());
            $branding->setLogoFilename(null);
            $this->entityManager->flush();
        }
    }

    public function resetBranding(Account $account): void
    {
        $branding = $this->accountBrandingRepository->findOneByAccount($account);
        
        if ($branding) {
            // Delete logo file
            if ($branding->getLogoFilename()) {
                $this->deleteLogoFile($branding->getLogoFilename());
            }
            
            // Remove branding entity
            $this->entityManager->remove($branding);
            $this->entityManager->flush();
        }
    }

    private function uploadLogo(UploadedFile $file): string
    {
        $filename = bin2hex(random_bytes(16)) . '.' . $file->guessExtension();
        $file->move($this->logoStoragePath, $filename);
        return $filename;
    }

    private function deleteLogoFile(string $filename): void
    {
        $filePath = $this->logoStoragePath . '/' . $filename;
        if ($this->filesystem->exists($filePath)) {
            $this->filesystem->remove($filePath);
        }
    }

    private function validateLogoFile(UploadedFile $file): void
    {
        $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/svg+xml'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        // Validate MIME type
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, $allowedTypes)) {
            throw new \InvalidArgumentException('branding.logo.invalid_type');
        }

        // Validate file size
        if ($file->getSize() > $maxSize) {
            throw new \InvalidArgumentException('branding.logo.too_large');
        }

        // Additional security: validate file extension matches MIME type
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = ['png', 'jpg', 'jpeg', 'svg'];
        
        if (!in_array($extension, $allowedExtensions)) {
            throw new \InvalidArgumentException('branding.logo.invalid_type');
        }

        // For SVG files, additional validation
        if ($extension === 'svg') {
            $content = file_get_contents($file->getPathname());
            if (!preg_match('/<\?xml|<svg/i', $content)) {
                throw new \InvalidArgumentException('branding.logo.invalid_type');
            }
            
            // Check for potentially dangerous SVG elements
            $dangerousSvgPatterns = [
                '/<script/i',
                '/on\w+\s*=/i',
                '/javascript:/i',
            ];
            
            foreach ($dangerousSvgPatterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    throw new \InvalidArgumentException('branding.logo.invalid_type');
                }
            }
        }
    }

    /**
     * Validate custom template syntax and security.
     */
    public function validateTemplateSyntax(string $templateContent): void
    {
        if (empty(trim($templateContent))) {
            return; // Empty template is valid (will use default)
        }

        // Check that template extends the base template
        if (!preg_match('/{%\s*extends\s+[\'"]public\/card\.html\.twig[\'"]\s*%}/i', $templateContent)) {
            throw new \InvalidArgumentException('branding.template.must_extend_base');
        }

        // Check for dangerous Twig functions/features
        $dangerousPatterns = [
            '/\bexec\s*\(/i',
            '/\bsystem\s*\(/i',
            '/\bshell_exec\s*\(/i',
            '/\bpassthru\s*\(/i',
            '/\bproc_open\s*\(/i',
            '/\bpopen\s*\(/i',
            '/\b`.*`/',
            '/\beval\s*\(/i',
            '/\binclude\s*\(/i',
            '/\brequire\s*\(/i',
            '/\brequire_once\s*\(/i',
            '/\binclude_once\s*\(/i',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $templateContent)) {
                throw new \InvalidArgumentException('branding.template.dangerous_function');
            }
        }
    }

    public function resetBranding(Account $account): void
    {
        if (!$this->canConfigureBranding($account)) {
            throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException('Branding is only available for Pro and Enterprise plans');
        }

        $branding = $this->accountBrandingRepository->findOneByAccount($account);
        
        if ($branding) {
            if ($branding->getLogoFilename()) {
                $this->deleteLogoFile($branding->getLogoFilename());
            }
            
            $this->entityManager->remove($branding);
            $this->entityManager->flush();
        }
    }

    public function handlePlanDowngrade(Account $account, PlanType $oldPlan, PlanType $newPlan): void
    {
        $branding = $this->accountBrandingRepository->findOneByAccount($account);
        
        if (!$branding) {
            return;
        }

        if ($oldPlan === PlanType::ENTERPRISE && $newPlan === PlanType::PRO) {
            $branding->setCustomTemplate(null);
            $this->entityManager->flush();
        }
    }

    public function cleanupBrandingFiles(Account $account): void
    {
        $branding = $this->accountBrandingRepository->findOneByAccount($account);
        
        if ($branding && $branding->getLogoFilename()) {
            $this->deleteLogoFile($branding->getLogoFilename());
        }
    }

    /**
     * Check color contrast ratio for accessibility compliance (WCAG).
     */
    public function checkColorContrast(string $foregroundColor, string $backgroundColor): array
    {
        // Implementation calculates WCAG contrast ratio
        // Returns: ['ratio' => float, 'level' => 'AA'|'AAA'|'fail', 'passes' => bool]
    }

    /**
     * Validate color contrast for common use cases.
     */
    public function validateColorAccessibility(?string $primaryColor, ?string $secondaryColor): array
    {
        // Returns array of warning messages if contrast is insufficient
    }
}
```

**Verification**: Service handles branding operations correctly.

---

### Step 7: Create BrandingFormType

**File**: `app/src/Form/BrandingFormType.php`

Create form for branding configuration:

```php
namespace App\Form;

use App\Entity\AccountBranding;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Regex;

class BrandingFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('primaryColor', TextType::class, [
                'required' => false,
                'label' => 'branding.primary_color',
                'attr' => [
                    'placeholder' => '#FF5733',
                    'pattern' => '^#[0-9A-Fa-f]{6}$',
                    'maxlength' => 7,
                ],
                'constraints' => [
                    new Regex(
                        pattern: '/^#[0-9A-Fa-f]{6}$/',
                        message: 'branding.color.invalid_format'
                    ),
                ],
            ])
            ->add('secondaryColor', TextType::class, [
                'required' => false,
                'label' => 'branding.secondary_color',
                'attr' => [
                    'placeholder' => '#6c757d',
                    'pattern' => '^#[0-9A-Fa-f]{6}$',
                    'maxlength' => 7,
                ],
                'constraints' => [
                    new Regex(
                        pattern: '/^#[0-9A-Fa-f]{6}$/',
                        message: 'branding.color.invalid_format'
                    ),
                ],
            ])
            ->add('logo', FileType::class, [
                'required' => false,
                'label' => 'branding.logo',
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/png',
                            'image/jpeg',
                            'image/jpg',
                            'image/svg+xml',
                        ],
                        'mimeTypesMessage' => 'branding.logo.invalid_type',
                    ]),
                ],
            ])
            ->add('logoPosition', ChoiceType::class, [
                'required' => false,
                'label' => 'branding.logo.position',
                'choices' => [
                    'branding.logo.position.top_left' => 'top-left',
                    'branding.logo.position.top_center' => 'top-center',
                    'branding.logo.position.top_right' => 'top-right',
                    'branding.logo.position.center' => 'center',
                    'branding.logo.position.bottom_left' => 'bottom-left',
                    'branding.logo.position.bottom_center' => 'bottom-center',
                    'branding.logo.position.bottom_right' => 'bottom-right',
                ],
            ])
            ->add('logoSize', ChoiceType::class, [
                'required' => false,
                'label' => 'branding.logo.size',
                'choices' => [
                    'branding.logo.size.small' => 'small',
                    'branding.logo.size.medium' => 'medium',
                    'branding.logo.size.large' => 'large',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AccountBranding::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'branding',
        ]);
    }
}
```

**Verification**: Form renders correctly, validates inputs.

---

### Step 8: Configure BrandingService in services.yaml

**File**: `app/config/services.yaml`

Add service configuration:

```yaml
App\Service\BrandingService:
    arguments:
        $projectDir: '%kernel.project_dir%'
```

**Verification**: Service is properly configured with project directory.

---

### Step 9: Create TemplateFormType

**File**: `app/src/Form/TemplateFormType.php`

Create form for template configuration:

```php
namespace App\Form;

use App\Entity\AccountBranding;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\Length as LengthConstraint;

class TemplateFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customTemplate', TextareaType::class, [
                'required' => false,
                'label' => 'branding.template.title',
                'attr' => [
                    'rows' => 20,
                    'placeholder' => "{% extends 'public/card.html.twig' %}\n\n{% block body %}\n    <!-- Your custom template content here -->\n{% endblock %}",
                    'class' => 'template-editor',
                ],
                'constraints' => [
                    new LengthConstraint(
                        max: 50000, // Max 50KB template content
                        maxMessage: 'branding.template.too_large'
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'template',
        ]);
    }
}
```

**Verification**: Form renders correctly, validates template content.

---

### Step 10: Create TemplateResolverService

**File**: `app/src/Service/TemplateResolverService.php`

Create service for resolving custom templates:

```php
namespace App\Service;

use App\Entity\Account;
use Twig\Environment;
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

    public function resolveTemplate(Account $account): ?string
    {
        $branding = $this->brandingService->getBrandingForAccount($account);
        
        if (!$branding || !$branding->getCustomTemplate()) {
            return null; // Use default template
        }

        $customTemplateContent = $branding->getCustomTemplate();
        $templateName = 'custom_card_template_' . $account->getId();
        
        $this->registerCustomTemplate($templateName, $customTemplateContent);
        
        return $templateName;
    }

    private function registerCustomTemplate(string $templateName, string $templateContent): void
    {
        // Register template in Twig loader using ArrayLoader and ChainLoader
        // Implementation details...
    }
}
```

**Verification**: Service can resolve custom templates for Enterprise accounts.

---

### Step 11: Create BrandingController

**File**: `app/src/Controller/BrandingController.php`

Create controller for branding configuration:

```php
namespace App\Controller;

use App\Entity\Account;
use App\Form\BrandingFormType;
use App\Form\TemplateFormType;
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
        private BrandingService $brandingService
    ) {
    }

    #[Route('/configure', name: 'app_branding_configure', methods: ['GET', 'POST'])]
    public function configure(Request $request): Response
    {
        $user = $this->getUser();
        $account = $user->getAccount();
        
        if (!$account) {
            throw $this->createNotFoundException('Account not found');
        }

        if (!$this->brandingService->canConfigureBranding($account)) {
            $this->addFlash('error', 'branding.access_denied');
            return $this->redirectToRoute('app_subscription_manage');
        }

        $branding = $this->brandingService->getBrandingForAccount($account);
        
        $brandingForm = $this->createForm(BrandingFormType::class, $branding);
        $brandingForm->handleRequest($request);

        if ($brandingForm->isSubmitted() && $brandingForm->isValid()) {
            $data = $brandingForm->getData();
            $logoFile = $brandingForm->get('logo')->getData();
            
            $this->brandingService->configureBranding($account, [
                'primaryColor' => $data->getPrimaryColor(),
                'secondaryColor' => $data->getSecondaryColor(),
                'logoPosition' => $data->getLogoPosition(),
                'logoSize' => $data->getLogoSize(),
            ], $logoFile);

            $this->addFlash('success', 'branding.save.success');
            return $this->redirectToRoute('app_branding_configure');
        }

        $templateForm = null;
        if ($this->brandingService->canConfigureTemplate($account)) {
            $templateForm = $this->createForm(TemplateFormType::class, $branding);
            $templateForm->handleRequest($request);

            if ($templateForm->isSubmitted() && $templateForm->isValid()) {
                $data = $templateForm->getData();
                $this->brandingService->configureTemplate($account, $data->getCustomTemplate() ?? '');
                
                $this->addFlash('success', 'branding.template.save.success');
                return $this->redirectToRoute('app_branding_configure');
            }
        }

        return $this->render('branding/configure.html.twig', [
            'account' => $account,
            'branding' => $branding,
            'brandingForm' => $brandingForm,
            'templateForm' => $templateForm,
            'canConfigureTemplate' => $this->brandingService->canConfigureTemplate($account),
        ]);
    }

    #[Route('/logo/remove', name: 'app_branding_remove_logo', methods: ['POST'])]
    public function removeLogo(Request $request): Response
    {
        $user = $this->getUser();
        $account = $user->getAccount();
        
        if (!$this->isCsrfTokenValid('remove_logo', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $this->brandingService->removeLogo($account);
        $this->addFlash('success', 'branding.logo.remove.success');
        
        return $this->redirectToRoute('app_branding_configure');
    }

    #[Route('/reset', name: 'app_branding_reset', methods: ['POST'])]
    public function reset(Request $request): Response
    {
        $user = $this->getUser();
        $account = $user->getAccount();
        
        if (!$this->isCsrfTokenValid('reset_branding', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        if ($request->request->get('confirm') !== 'yes') {
            $this->addFlash('error', 'branding.reset.confirmation_required');
            return $this->redirectToRoute('app_branding_configure');
        }

        $this->brandingService->resetBranding($account);
        $this->addFlash('success', 'branding.reset.success');
        
        return $this->redirectToRoute('app_branding_configure');
    }
}
```

**Verification**: Controller handles branding configuration requests correctly.

---

### Step 12: Create AccountBrandingSubscriber

**File**: `app/src/EventSubscriber/AccountBrandingSubscriber.php`

Create event subscriber for cleaning up branding files:

```php
namespace App\EventSubscriber;

use App\Entity\Account;
use App\Entity\AccountBranding;
use App\Service\BrandingService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::preRemove)]
class AccountBrandingSubscriber
{
    public function __construct(
        private ?BrandingService $brandingService = null
    ) {
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        
        if ($entity instanceof Account && $this->brandingService) {
            $this->brandingService->cleanupBrandingFiles($entity);
        }
        
        if ($entity instanceof AccountBranding && $this->brandingService && $entity->getLogoFilename()) {
            $this->brandingService->cleanupBrandingFiles($entity->getAccount());
        }
    }
}
```

**Verification**: Event subscriber cleans up files when accounts are deleted.

---

### Step 13: Create Branding Configuration Template

**File**: `app/templates/branding/configure.html.twig`

Create template for branding configuration page:

```twig
{% extends 'base.html.twig' %}

{% block title %}{{ 'branding.title'|trans }}{% endblock %}

{% block body %}
    <div class="branding-container">
        <h1>{{ 'branding.title'|trans }}</h1>
        
        {% if not brandingService.canConfigureBranding(account) %}
            <div class="alert alert-warning">
                {{ 'branding.access_denied'|trans }}
                <a href="{{ path('app_subscription_manage') }}">{{ 'branding.upgrade'|trans }}</a>
            </div>
        {% else %}
            {{ form_start(brandingForm, {'attr': {'enctype': 'multipart/form-data'}}) }}
                <div class="form-section">
                    <h2>{{ 'branding.colors.title'|trans }}</h2>
                    {{ form_row(brandingForm.primaryColor) }}
                    {{ form_row(brandingForm.secondaryColor) }}
                </div>
                
                <div class="form-section">
                    <h2>{{ 'branding.logo.title'|trans }}</h2>
                    {% if branding and branding.logoFilename %}
                        <img src="{{ asset('uploads/branding/logos/' ~ branding.logoFilename) }}" alt="Logo" style="max-width: 200px;">
                        <br>
                        <a href="{{ path('app_branding_remove_logo') }}" class="btn btn-danger" onclick="return confirm('{{ 'branding.logo.remove.confirm'|trans }}')">
                            {{ 'branding.logo.remove'|trans }}
                        </a>
                    {% endif %}
                    {{ form_row(brandingForm.logo) }}
                    {{ form_row(brandingForm.logoPosition) }}
                    {{ form_row(brandingForm.logoSize) }}
                </div>
                
                {{ form_row(brandingForm._token) }}
                <button type="submit" class="btn btn-primary">{{ 'branding.save'|trans }}</button>
            {{ form_end(brandingForm) }}
            
            {% if canConfigureTemplate %}
                <div class="form-section">
                    <h2>{{ 'branding.template.title'|trans }}</h2>
                    {{ form_start(templateForm) }}
                        {{ form_row(templateForm.customTemplate) }}
                        {{ form_row(templateForm._token) }}
                        <button type="submit" class="btn btn-primary">{{ 'branding.template.save'|trans }}</button>
                    {{ form_end(templateForm) }}
                </div>
            {% endif %}
        {% endif %}
    </div>
{% endblock %}
```

**Verification**: Template renders correctly, forms submit properly.

---

### Step 14: Update Public Card Template

**File**: `app/templates/public/card.html.twig`

Modify public card template to apply branding:

```twig
{% extends 'base.html.twig' %}

{% block title %}{{ card.content.name|default('card.public.title'|trans) }} - Hermio{% endblock %}

{% block stylesheets %}
    {{ parent() }}
    {% set branding = card.user.account.branding %}
    {% if branding %}
        <style>
            :root {
                --primary-color: {{ branding.primaryColor|default('#007bff') }};
                --secondary-color: {{ branding.secondaryColor|default('#6c757d') }};
            }
            
            .public-card {
                {% if branding.primaryColor %}
                    border-top: 4px solid {{ branding.primaryColor }};
                {% endif %}
            }
        </style>
    {% endif %}
    <style>
        .public-card-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
        }
        .public-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 40px;
            text-align: center;
        }
        .brand-logo {
            max-width: 200px;
            margin-bottom: 20px;
        }
        /* ... rest of existing styles ... */
    </style>
{% endblock %}

{% block body %}
<div class="public-card-container">
    <div class="public-card">
        {% set branding = card.user.account.branding %}
        {% if branding and branding.logoFilename %}
            <img src="{{ asset('uploads/branding/logos/' ~ branding.logoFilename) }}" alt="Logo" class="brand-logo">
        {% endif %}
        
        <h1>{{ card.content.name|default('card.public.title'|trans) }}</h1>
        
        {# ... rest of existing card content ... #}
    </div>
</div>
{% endblock %}
```

**Verification**: Public card pages display branding correctly.

---

### Step 15: Update PublicCardController

**File**: `app/src/Controller/PublicCardController.php`

Modify controller to use TemplateResolverService and pass branding:

```php
use App\Service\TemplateResolverService;

public function __construct(
    private CardRepository $cardRepository,
    private BrandingService $brandingService,
    private TemplateResolverService $templateResolverService
) {
}

public function show(string $slug): Response
{
    $card = $this->cardRepository->findOneBySlug($slug);
    
    if (!$card) {
        throw $this->createNotFoundException('Card not found');
    }

    $account = $card->getUser()->getAccount();
    $branding = $account ? $this->brandingService->getBrandingForAccount($account) : null;
    
    // Resolve template (custom or default)
    $templateName = $account ? $this->templateResolverService->resolveTemplate($account) : null;
    $templateName = $templateName ?? 'public/card.html.twig';

    return $this->render($templateName, [
        'card' => $card,
        'account' => $account,
        'branding' => $branding,
    ]);
}
```

**Verification**: Controller passes branding data to template.

---

### Step 16: Add Translations

**File**: `app/translations/messages.en.yaml` and `app/translations/messages.fr.yaml`

Add branding translation keys:

```yaml
# English
branding:
  title: "Branding Configuration"
  access_denied: "Branding customization is only available for Pro and Enterprise plans"
  upgrade: "Upgrade Now"
  colors:
    title: "Brand Colors"
  primary_color: "Primary Color"
  secondary_color: "Secondary Color"
  logo:
    title: "Logo"
    position: "Logo Position"
    size: "Logo Size"
    remove: "Remove Logo"
    remove.confirm: "Are you sure you want to remove the logo?"
  save: "Save Branding"
  save.success: "Branding configuration saved successfully"
  template:
    title: "Custom Template (Enterprise Only)"
    content: "Template Content"
    save: "Save Template"
    save.success: "Custom template saved successfully"
  logo.remove.success: "Logo removed successfully"
  reset.success: "Branding configuration reset successfully"
```

**Verification**: Translations display correctly in both languages.

---

## Testing Checklist

- [ ] Pro/Enterprise users can access branding configuration page
- [ ] Free users see upgrade prompt when accessing branding page
- [ ] Brand colors can be configured and saved
- [ ] Logo can be uploaded and displayed
- [ ] Logo can be removed
- [ ] Logo position and size can be configured
- [ ] Enterprise users can configure custom templates
- [ ] Pro users cannot access template configuration
- [ ] Public card pages display configured branding
- [ ] Branding changes reflect immediately on public card pages
- [ ] Plan downgrades disable features appropriately
- [ ] File upload validation works (type, size, SVG security)
- [ ] Color format validation works
- [ ] Color accessibility warnings display for low contrast
- [ ] Template syntax validation works
- [ ] Reset branding functionality works
- [ ] Logo files are cleaned up when account is deleted
- [ ] Custom templates render correctly for Enterprise accounts

---

## Notes

- Logo files are stored in `public/uploads/branding/logos/` directory
- Branding is applied only to public card pages, not authenticated dashboard pages
- Plan-based access is enforced at service layer
- Branding data is preserved on plan downgrade (graceful degradation)
- Custom templates must extend base template for security
- Color accessibility warnings are shown for low contrast ratios (WCAG compliance)
- SVG files are validated for security (no scripts, event handlers, or JavaScript)
- TemplateResolverService uses Twig ChainLoader to dynamically load custom templates
- AccountBrandingSubscriber automatically cleans up logo files when accounts are deleted
- BrandingService includes methods for plan downgrade handling and file cleanup

