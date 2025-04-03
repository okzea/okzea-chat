/**
 * Okzea Modal
 * 
 * Adds a modal with iframe to the page that opens when clicking links with href="#contact"
 */

class OkzeaModal {
  constructor() {
    this.initialized = false;
    this.iframeLoaded = false;
    this.createModal();
    this.setupEventListeners();
  }

  createModal() {
    // Create modal styles
    const style = document.createElement('style');
    style.textContent = `
      .okzea-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
        backdrop-filter: blur(10px);
      }
      .okzea-modal.show {
        opacity: 1;
      }
      .okzea-modal-content {
        position: relative;
        width: 90%;
        max-width: 800px;
        height: 80%;
        max-height: 600px;
        background-color: #19191b;
        border-radius: 8px;
        overflow: hidden;
        transform: translateY(50px);
        transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        
        @media (max-width: 768px) {
          width: 100%;
          height: 100%;
          max-width: none;
          max-height: none;
          border-radius: 0;
        }
      }
      .okzea-modal.show .okzea-modal-content {
        transform: translateY(0);
      }
      .okzea-modal-close {
        position: absolute;
        top: 30px;
        right: 30px;
        width: 30px;
        height: 30px;
        color: rgb(122, 122, 122);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-weight: bold;
        z-index: 10;
        transition: color 0.2s ease;
      }
      .okzea-modal-close:hover {
        transform: scale(1.1);
        color: rgb(145, 145, 145);
      }
      .okzea-modal-close svg {
        width: 20px;
        height: 20px;
        fill: currentColor;
        transition: fill 0.2s ease;
      }
      .okzea-modal-iframe {
        width: 100%;
        height: 100%;
        border: none;
        background-color: #19191b;
      }
    `;
    document.head.appendChild(style);

    // Create modal HTML
    this.modal = document.createElement('div');
    this.modal.className = 'okzea-modal';
    this.modal.innerHTML = `
      <div class="okzea-modal-content">
        <div class="okzea-modal-close">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="512" height="512"><path d="m12,0C5.383,0,0,5.383,0,12s5.383,12,12,12,12-5.383,12-12S18.617,0,12,0Zm3.707,14.293c.391.391.391,1.023,0,1.414-.195.195-.451.293-.707.293s-.512-.098-.707-.293l-2.293-2.293-2.293,2.293c-.195.195-.451.293-.707.293s-.512-.098-.707-.293c-.391-.391-.391-1.023,0-1.414l2.293-2.293-2.293-2.293c-.391-.391-.391-1.023,0-1.414s1.023-.391,1.414,0l2.293,2.293,2.293-2.293c.391-.391,1.023-.391,1.414,0s.391,1.023,0,1.414l-2.293,2.293,2.293,2.293Z"/></svg>
        </div>
        <iframe class="okzea-modal-iframe" title="Okzea Chat"></iframe>
      </div>
    `;
    document.body.appendChild(this.modal);
    this.iframe = this.modal.querySelector('.okzea-modal-iframe');
    this.initialized = true;
  }

  setupEventListeners() {
    // Setup contact links
    this.setupContactLinks();
    
    // Close modal when clicking the close button or outside the modal
    const closeBtn = this.modal.querySelector('.okzea-modal-close');
    closeBtn.addEventListener('click', () => this.closeModal());

    this.modal.addEventListener('click', (e) => {
      if (e.target === this.modal) {
        this.closeModal();
      }
    });

    // Close modal when pressing Escape
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && this.modal.classList.contains('show')) {
        this.closeModal();
      }
    });

    // Watch for new links being added to the DOM
    this.observeDOM();
  }

  setupContactLinks() {
    const contactLinks = document.querySelectorAll('a[href="#contact"]');
    contactLinks.forEach(link => {
      // Only attach event if it doesn't already have one
      if (!link.hasAttribute('data-okzea-modal')) {
        link.setAttribute('data-okzea-modal', 'true');
        link.addEventListener('click', (e) => {
          e.preventDefault();
          this.openModal();
        });
      }
    });
  }

  openModal() {
    // Load iframe content if not already loaded
    if (!this.iframeLoaded) {
      this.iframe.src = '/chat';
      this.iframeLoaded = true;
    }
    
    // Lock body scroll
    document.body.style.overflow = 'hidden';
    
    this.modal.style.display = 'flex';
    // Trigger animation after a tiny delay to ensure display:flex is applied
    setTimeout(() => {
      this.modal.classList.add('show');
    }, 10);
  }

  closeModal() {
    this.modal.classList.remove('show');
    setTimeout(() => {
      this.modal.style.display = 'none';
      // Restore body scroll
      document.body.style.overflow = '';
    }, 300); // Match the opacity transition duration
  }

  observeDOM() {
    // Create an observer instance linked to the callback function
    const observer = new MutationObserver(() => {
      this.setupContactLinks();
    });
    
    // Start observing the document body for DOM changes
    observer.observe(document.body, { 
      childList: true,
      subtree: true 
    });
  }
}

// Initialize the modal when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', () => {
  new OkzeaModal();
});

// Export the class for potential use in other modules
export default OkzeaModal; 
