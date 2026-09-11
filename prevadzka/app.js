// Rezervos Prevadzka - Interactive Application JS

document.addEventListener('DOMContentLoaded', () => {
  // Elements
  const progressBar = document.getElementById('progress-bar');
  const searchInput = document.getElementById('search-input');
  const filterPills = document.querySelectorAll('.filter-pill');
  const guideSections = document.querySelectorAll('.guide-section');
  const navItems = document.querySelectorAll('.sidebar-nav-item');
  const themeToggle = document.getElementById('theme-toggle');
  const mobileToggle = document.getElementById('mobile-toggle');
  const sidebar = document.querySelector('.sidebar');
  
  // Lightbox elements
  const lightboxModal = document.getElementById('lightbox-modal');
  const lightboxImg = document.getElementById('lightbox-img');
  const lightboxCaption = document.getElementById('lightbox-caption');
  const lightboxClose = document.getElementById('lightbox-close');

  // 1. Reading Progress Bar
  window.addEventListener('scroll', () => {
    const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
    const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
    const scrolled = (winScroll / height) * 100;
    if (progressBar) {
      progressBar.style.width = scrolled + '%';
    }
  });

  // 2. Dark / Light Mode Toggle
  const updateThemeIcon = (isDark) => {
    if (!themeToggle) return;
    themeToggle.innerHTML = isDark 
      ? '<span class="material-symbols-outlined">light_mode</span>' 
      : '<span class="material-symbols-outlined">dark_mode</span>';
  };

  const currentTheme = localStorage.getItem('rezervos-theme') || 'light';
  if (currentTheme === 'dark') {
    document.documentElement.setAttribute('data-theme', 'dark');
    document.documentElement.classList.add('dark-mode');
    updateThemeIcon(true);
  } else {
    updateThemeIcon(false);
  }

  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
      if (isDark) {
        document.documentElement.removeAttribute('data-theme');
        document.documentElement.classList.remove('dark-mode');
        localStorage.setItem('rezervos-theme', 'light');
        updateThemeIcon(false);
      } else {
        document.documentElement.setAttribute('data-theme', 'dark');
        document.documentElement.classList.add('dark-mode');
        localStorage.setItem('rezervos-theme', 'dark');
        updateThemeIcon(true);
      }
    });
  }

  // 3. Mobile Navigation Toggle & Backdrop
  const sidebarClose = document.getElementById('sidebar-close');
  const sidebarBackdrop = document.getElementById('sidebar-backdrop');

  const openMobileNav = () => {
    if (sidebar) sidebar.classList.add('mobile-open');
    if (sidebarBackdrop) sidebarBackdrop.classList.add('active');
    document.body.style.overflow = 'hidden';
  };

  const closeMobileNav = () => {
    if (sidebar) sidebar.classList.remove('mobile-open');
    if (sidebarBackdrop) sidebarBackdrop.classList.remove('active');
    document.body.style.overflow = '';
  };

  if (mobileToggle) mobileToggle.addEventListener('click', openMobileNav);
  if (sidebarClose) sidebarClose.addEventListener('click', closeMobileNav);
  if (sidebarBackdrop) sidebarBackdrop.addEventListener('click', closeMobileNav);

  // Close mobile nav on link click
  document.querySelectorAll('.sidebar-nav-item a').forEach(link => {
    link.addEventListener('click', closeMobileNav);
  });

  // 4. Search Filter
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      const term = e.target.value.toLowerCase().trim();
      
      guideSections.forEach(section => {
        const text = section.innerText.toLowerCase();
        const matches = text.includes(term);
        section.style.display = matches ? 'block' : 'none';
        
        // Match sidebar link
        const secId = section.getAttribute('id');
        const navLink = document.querySelector(`.sidebar-nav-item a[href="#${secId}"]`);
        if (navLink) {
          const parentLi = navLink.parentElement;
          parentLi.style.display = matches ? 'block' : 'none';
        }
      });
    });
  }

  // 5. Category Pills Filter
  filterPills.forEach(pill => {
    pill.addEventListener('click', () => {
      filterPills.forEach(p => p.classList.remove('active'));
      pill.classList.add('active');

      const category = pill.getAttribute('data-category');

      guideSections.forEach(section => {
        const secCat = section.getAttribute('data-category');
        if (category === 'all' || secCat === category) {
          section.style.display = 'block';
        } else {
          section.style.display = 'none';
        }
      });

      // Clear search input on pill change
      if (searchInput) searchInput.value = '';
    });
  });

  // 6. Lightbox Handler
  document.querySelectorAll('.screenshot-container').forEach(container => {
    container.addEventListener('click', () => {
      const img = container.querySelector('img');
      const caption = container.getAttribute('data-caption') || img.getAttribute('alt') || 'Náhľad systému Rezervos';
      
      if (img && lightboxModal && lightboxImg) {
        lightboxImg.src = img.src;
        if (lightboxCaption) lightboxCaption.textContent = caption;
        lightboxModal.classList.add('open');
      }
    });
  });

  const closeLightbox = () => {
    if (lightboxModal) lightboxModal.classList.remove('open');
  };

  if (lightboxClose) lightboxClose.addEventListener('click', closeLightbox);
  if (lightboxModal) {
    lightboxModal.addEventListener('click', (e) => {
      if (e.target === lightboxModal) closeLightbox();
    });
  }
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeLightbox();
  });

  // 7. Active ScrollSpy for Sidebar
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const id = entry.target.getAttribute('id');
        navItems.forEach(item => {
          const link = item.querySelector('a');
          if (link && link.getAttribute('href') === `#${id}`) {
            item.classList.add('active');
          } else {
            item.classList.remove('active');
          }
        });
      }
    });
  }, { threshold: 0.25 });

  guideSections.forEach(sec => observer.observe(sec));
});
