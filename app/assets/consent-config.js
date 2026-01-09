/**
 * Configuration du gestionnaire de consentement RGPD
 * Configuration for GDPR consent manager
 */

export function initConsentManager() {
    if (typeof window !== 'undefined' && window.silktideConsentManager) {
        window.silktideConsentManager.init({
            consentTypes: [
                {
                    id: 'essential',
                    label: 'Essentiels',
                    description: 'Ces cookies sont nécessaires au fonctionnement du site et ne peuvent pas être désactivés.',
                    required: true,
                },
                {
                    id: 'analytics',
                    label: 'Analytiques',
                    description: 'Ces cookies nous aident à comprendre comment les visiteurs interagissent avec le site web.',
                    defaultValue: false,
                    gtag: 'analytics_storage',
                    onAccept: function() {
                        console.log('Cookies analytiques acceptés');
                    },
                    onReject: function() {
                        console.log('Cookies analytiques refusés');
                    },
                },
                {
                    id: 'marketing',
                    label: 'Marketing',
                    description: 'Ces cookies sont utilisés pour diffuser des publicités personnalisées.',
                    defaultValue: false,
                    gtag: ['ad_storage', 'ad_user_data', 'ad_personalization'],
                    onAccept: function() {
                        console.log('Cookies marketing acceptés');
                    },
                    onReject: function() {
                        console.log('Cookies marketing refusés');
                    },
                },
            ],
            text: {
                prompt: {
                    description: '<p>Nous utilisons des cookies pour améliorer votre expérience sur notre site. Vous pouvez accepter tous les cookies ou gérer vos préférences.</p>',
                    acceptAllButtonText: 'Tout accepter',
                    acceptAllButtonAccessibleLabel: 'Accepter tous les cookies',
                    rejectNonEssentialButtonText: 'Refuser les cookies non essentiels',
                    rejectNonEssentialButtonAccessibleLabel: 'Refuser tous les cookies non essentiels',
                    preferencesButtonText: 'Préférences',
                    preferencesButtonAccessibleLabel: 'Gérer les préférences des cookies',
                },
                preferences: {
                    title: 'Personnalisez vos préférences',
                    description: '<p>Choisissez les cookies que vous souhaitez accepter. Vous pouvez modifier vos préférences à tout moment.</p>',
                    saveButtonText: 'Enregistrer et fermer',
                    saveButtonAccessibleLabel: 'Enregistrer vos préférences de cookies',
                    creditLinkText: 'Obtenir ce gestionnaire de consentement gratuitement',
                    creditLinkAccessibleLabel: 'Visitez Silktide Consent Manager',
                },
            },
            prompt: {
                position: 'bottomCenter' // Options: 'center', 'bottomLeft', 'bottomCenter', 'bottomRight'
            },
            icon: {
                position: 'bottomLeft' // Options: 'bottomLeft', 'bottomRight'
            },
            eventName: 'hermio_consent_update',
        });
    }
}
