# Form Field Specifications

**Date**: December 8, 2025  
**Feature**: User Account & Authentication  
**Framework**: Symfony Forms with Twig rendering

## Overview

This document defines the exact form field specifications, validation rules, and rendering requirements for all authentication forms. All forms follow Symfony Form component patterns with Twig theming.

## Form Type Classes

### 1. RegistrationFormType

```php
namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email address',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your email address',
                    'autocomplete' => 'email'
                ]
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => 'The password fields must match.',
                'first_options' => [
                    'label' => 'Password',
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Choose a secure password',
                        'autocomplete' => 'new-password'
                    ]
                ],
                'second_options' => [
                    'label' => 'Confirm Password',
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Repeat your password',
                        'autocomplete' => 'new-password'
                    ]
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label' => 'I agree to the Terms of Service and Privacy Policy',
                'constraints' => [
                    new IsTrue([
                        'message' => 'You should agree to our terms.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
```

### 2. LoginFormType

```php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;

class LoginFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Your email address',
                    'autocomplete' => 'email',
                    'autofocus' => true
                ]
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Password',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Your password',
                    'autocomplete' => 'current-password'
                ]
            ])
            ->add('_remember_me', CheckboxType::class, [
                'label' => 'Remember me',
                'required' => false,
            ]);
    }
}
```

### 3. PasswordResetRequestFormType

```php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PasswordResetRequestFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email address',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your email address',
                    'autocomplete' => 'email'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter your email',
                    ]),
                ],
            ]);
    }
}
```

### 4. ChangePasswordFormType

```php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => 'The password fields must match.',
                'first_options' => [
                    'label' => 'New Password',
                    'attr' => [
                        'class' => 'form-control',
                        'autocomplete' => 'new-password'
                    ]
                ],
                'second_options' => [
                    'label' => 'Repeat Password',
                    'attr' => [
                        'class' => 'form-control',
                        'autocomplete' => 'new-password'
                    ]
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        'max' => 4096,
                    ]),
                ],
            ]);
    }
}
```

### 5. UserProfileFormType

```php
namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email address',
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'email'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
```

## Form Validation Rules

### Password Validation

```php
namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class PasswordStrengthValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $score = 0;
        
        // Length check
        if (strlen($value) >= 8) $score++;
        
        // Lowercase check
        if (preg_match('/[a-z]/', $value)) $score++;
        
        // Uppercase check
        if (preg_match('/[A-Z]/', $value)) $score++;
        
        // Number check
        if (preg_match('/\d/', $value)) $score++;
        
        // Special character check
        if (preg_match('/[^A-Za-z0-9]/', $value)) $score++;

        if ($score < $constraint->minScore) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ score }}', $score)
                ->setParameter('{{ min_score }}', $constraint->minScore)
                ->addViolation();
        }
    }
}
```

### Email Uniqueness Validation

```php
namespace App\Validator;

use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueEmailValidator extends ConstraintValidator
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function validate($value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        $existingUser = $this->userRepository->findOneBy(['email' => $value]);
        
        if ($existingUser) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
```

## Form Themes and Rendering

### Base Form Theme

```twig
{# templates/form/form_theme.html.twig #}

{% block form_row %}
    <div class="mb-3">
        {{ form_label(form) }}
        {{ form_widget(form) }}
        {{ form_errors(form) }}
    </div>
{% endblock %}

{% block form_errors %}
    {% if errors|length > 0 %}
        <div class="invalid-feedback d-block">
            {% for error in errors %}
                {{ error.message }}
            {% endfor %}
        </div>
    {% endif %}
{% endblock %}

{% block email_widget %}
    {% set attr = attr|merge({class: (attr.class|default('') ~ ' form-control')|trim}) %}
    {{ parent() }}
{% endblock %}

{% block password_widget %}
    {% set attr = attr|merge({class: (attr.class|default('') ~ ' form-control')|trim}) %}
    {{ parent() }}
{% endblock %}
```

### Registration Form Template

```twig
{# templates/security/register.html.twig #}

{% extends 'base.html.twig' %}
{% form_theme registrationForm 'form/form_theme.html.twig' %}

{% block title %}Register{% endblock %}

{% block body %}
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h1 class="h3 mb-3">Create Your Account</h1>
            
            {{ form_start(registrationForm) }}
                {{ form_row(registrationForm.email) }}
                
                <div class="row">
                    <div class="col-md-6">
                        {{ form_row(registrationForm.plainPassword.first) }}
                    </div>
                    <div class="col-md-6">
                        {{ form_row(registrationForm.plainPassword.second) }}
                    </div>
                </div>
                
                <div class="password-requirements mb-3">
                    <small class="text-muted">
                        Password must contain:
                        <ul class="mb-0">
                            <li>At least 8 characters</li>
                            <li>One uppercase letter</li>
                            <li>One lowercase letter</li>
                            <li>One number</li>
                            <li>One special character</li>
                        </ul>
                    </small>
                </div>
                
                {{ form_row(registrationForm.agreeTerms) }}
                
                <button type="submit" class="btn btn-primary btn-lg w-100">
                    Create Account
                </button>
            {{ form_end(registrationForm) }}
            
            <div class="text-center mt-3">
                <p>Already have an account? <a href="{{ path('app_login') }}">Sign in here</a></p>
            </div>
        </div>
    </div>
</div>
{% endblock %}
```

### Login Form Template

```twig
{# templates/security/login.html.twig #}

{% extends 'base.html.twig' %}
{% form_theme loginForm 'form/form_theme.html.twig' %}

{% block title %}Log in{% endblock %}

{% block body %}
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <h1 class="h3 mb-3">Sign In</h1>
            
            {% if error %}
                <div class="alert alert-danger" role="alert">
                    {{ error.messageKey|trans(error.messageData, 'security') }}
                </div>
            {% endif %}
            
            {% if app.user %}
                <div class="mb-3">
                    You are logged in as {{ app.user.userIdentifier }}, 
                    <a href="{{ path('app_logout') }}">Logout</a>
                </div>
            {% endif %}

            {{ form_start(loginForm) }}
                {{ form_row(loginForm.email) }}
                {{ form_row(loginForm.password) }}
                {{ form_row(loginForm._remember_me) }}
                
                <input type="hidden" name="_csrf_token" value="{{ csrf_token('authenticate') }}">
                
                <button class="btn btn-primary btn-lg w-100" type="submit">
                    Sign In
                </button>
            {{ form_end(loginForm) }}
            
            <div class="text-center mt-3">
                <p><a href="{{ path('app_forgot_password') }}">Forgot your password?</a></p>
                <p>Don't have an account? <a href="{{ path('app_register') }}">Create one here</a></p>
            </div>
        </div>
    </div>
</div>
{% endblock %}
```

## JavaScript Enhancements

### Registration Controller (Stimulus)

```javascript
// assets/controllers/registration_controller.js

import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["password", "confirmation", "strength", "requirements"]
    
    connect() {
        this.checkPasswordStrength()
    }
    
    checkPasswordStrength() {
        this.passwordTarget.addEventListener('input', (e) => {
            const password = e.target.value
            const strength = this.calculateStrength(password)
            this.updateStrengthIndicator(strength)
            this.updateRequirements(password)
        })
    }
    
    calculateStrength(password) {
        let score = 0
        if (password.length >= 8) score++
        if (/[a-z]/.test(password)) score++
        if (/[A-Z]/.test(password)) score++
        if (/\d/.test(password)) score++
        if (/[^A-Za-z0-9]/.test(password)) score++
        return score
    }
    
    updateStrengthIndicator(score) {
        const colors = ['danger', 'danger', 'warning', 'info', 'success', 'success']
        const labels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong', 'Very Strong']
        
        this.strengthTarget.className = `badge bg-${colors[score]}`
        this.strengthTarget.textContent = labels[score]
    }
    
    updateRequirements(password) {
        const requirements = [
            { test: password.length >= 8, text: 'At least 8 characters' },
            { test: /[a-z]/.test(password), text: 'One lowercase letter' },
            { test: /[A-Z]/.test(password), text: 'One uppercase letter' },
            { test: /\d/.test(password), text: 'One number' },
            { test: /[^A-Za-z0-9]/.test(password), text: 'One special character' }
        ]
        
        requirements.forEach((req, index) => {
            const element = this.requirementsTarget.children[index]
            if (req.test) {
                element.classList.add('text-success')
                element.classList.remove('text-muted')
            } else {
                element.classList.add('text-muted')
                element.classList.remove('text-success')
            }
        })
    }
}
```

### Password Visibility Controller

```javascript
// assets/controllers/password_controller.js

import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["field", "toggle"]
    
    toggle() {
        if (this.fieldTarget.type === "password") {
            this.fieldTarget.type = "text"
            this.toggleTarget.innerHTML = '<i class="bi bi-eye-slash"></i>'
        } else {
            this.fieldTarget.type = "password"
            this.toggleTarget.innerHTML = '<i class="bi bi-eye"></i>'
        }
    }
}
```

## Form Security

### CSRF Protection

All forms include CSRF tokens:

```php
// In controllers
$form = $this->createForm(RegistrationFormType::class, $user, [
    'csrf_protection' => true,
    'csrf_field_name' => '_token',
    'csrf_token_id'   => 'registration',
]);
```

### Rate Limiting

Forms implement rate limiting via annotations:

```php
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[Route('/register', name: 'app_register')]
public function register(Request $request, RateLimiterFactory $registrationLimiter): Response
{
    $limiter = $registrationLimiter->create($request->getClientIp());
    if (false === $limiter->consume(1)->isAccepted()) {
        throw new TooManyRequestsHttpException();
    }
    
    // Form handling...
}
```

### Input Sanitization

All form inputs are automatically escaped by Twig and validated by Symfony constraints. Additional sanitization for special cases:

```php
use Symfony\Component\String\Slugger\SluggerInterface;

public function sanitizeInput(string $input, SluggerInterface $slugger): string
{
    // Remove HTML tags
    $clean = strip_tags($input);
    
    // Trim whitespace
    $clean = trim($clean);
    
    // Additional sanitization as needed
    return $clean;
}
```