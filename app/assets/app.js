/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';

// Import Silktide Consent Manager CSS
import './styles/silktide-consent-manager.css';

// Import Silktide Consent Manager JS
import './silktide-consent-manager.js';

// Import consent configuration
import { initConsentManager } from './consent-config.js';

// Initialize consent manager when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initConsentManager();
});

