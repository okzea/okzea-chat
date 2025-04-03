/**
 * Okzea Contact Modal
 * 
 * Main entry point for the contact modal feature.
 * This script handles the modal that appears when clicking on links with href="#contact"
 */

import OkzeaModal from './okzea-modal';

// Initialize the modal when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', () => {
  console.log('DOMContentLoaded');
  new OkzeaModal();
}); 
