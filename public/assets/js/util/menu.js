export const setHereClassForMenu = (menuContainerSelector) => {
  const menuContainer = document.querySelector(menuContainerSelector);
  if (!menuContainer) return;

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

  const links = menuContainer.querySelectorAll('a.menu-link[href]');
  let activeLink = null;
  let bestMatchLength = 0;

  links.forEach(link => {
    const linkPath = normalize(link.href);
    if (!linkPath) return;

    // Exact match OR partial match
    if (
      linkPath === currentPath ||
      (currentPath.startsWith(linkPath) && linkPath.length > bestMatchLength)
    ) {
      activeLink = link;
      bestMatchLength = linkPath.length;
    }
  });

  if (!activeLink) {
    console.warn('No active menu match for:', currentPath);
    return;
  }

  // Apply active to link
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