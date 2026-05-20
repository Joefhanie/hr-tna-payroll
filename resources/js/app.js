document.addEventListener('DOMContentLoaded', () => {
	const toggles = document.querySelectorAll('[data-submenu-toggle]');

	toggles.forEach((toggle) => {
		const targetId = toggle.getAttribute('data-submenu-toggle');
		const submenu = targetId ? document.getElementById(targetId) : null;

		if (!submenu) {
			return;
		}

		toggle.addEventListener('click', () => {
			const isHidden = submenu.classList.contains('hidden');
			submenu.classList.toggle('hidden');
			toggle.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
		});
	});

	const appShell = document.getElementById('app-shell');
	const collapseToggle = document.getElementById('sidebar-collapse-toggle');

	if (appShell && collapseToggle) {
		const storageKey = 'sidebar-collapsed';
		const collapseIcon = document.getElementById('sidebar-collapse-icon');
		const applyState = (collapsed) => {
			appShell.classList.toggle('sidebar-collapsed', collapsed);
			collapseToggle.setAttribute('aria-pressed', collapsed ? 'true' : 'false');
			if (collapseIcon) {
				collapseIcon.classList.toggle('fa-angles-left', !collapsed);
				collapseIcon.classList.toggle('fa-angles-right', collapsed);
			}
		};

		const savedState = window.localStorage.getItem(storageKey);
		applyState(savedState === '1');

		collapseToggle.addEventListener('click', () => {
			const collapsed = !appShell.classList.contains('sidebar-collapsed');
			applyState(collapsed);
			window.localStorage.setItem(storageKey, collapsed ? '1' : '0');
		});
	}
});
