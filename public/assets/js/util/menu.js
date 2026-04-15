export const setHereClassForMenu = (menuContainerSelector) => {
  const menuContainer = document.querySelector(menuContainerSelector);
  if (!menuContainer) return;

  // Normalize current path
  const normalize = (url) => {
    try {
      const u = new URL(url, window.location.origin);
      return (u.pathname || '/').replace(/\/+$/, '') || '/';
    } catch {
      return null;
    }
  };

  const currentPath = normalize(window.location.href);

  // Reset previous state
  menuContainer
    .querySelectorAll('.active, .here, .show')
    .forEach(el => el.classList.remove('active', 'here', 'show'));

  // Find active link
  const links = menuContainer.querySelectorAll('a.menu-link[href]');
  let activeLink = null;

  for (let i = 0; i < links.length; i++) {
    const linkPath = normalize(links[i].href);

    if (linkPath === currentPath) {
      activeLink = links[i];
      break;
    }
  }

  if (!activeLink) {
    console.warn('No active menu match for:', currentPath);
    return;
  }

  // Apply active ONLY to link
  activeLink.classList.add('active');

  // Apply here/show to parents
  let parentItem = activeLink.closest('.menu-item');

  // Skip leaf → go to parent
  parentItem = parentItem?.parentElement?.closest('.menu-item');

  while (parentItem && parentItem !== menuContainer) {
    if (parentItem.classList.contains('menu-accordion')) {
      parentItem.classList.add('here', 'show');
    }
    parentItem = parentItem.parentElement?.closest('.menu-item');
  }
};