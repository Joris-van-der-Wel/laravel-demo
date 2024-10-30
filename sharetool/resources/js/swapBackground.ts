const swapBackgrounds = () => {
    if (document.visibilityState === 'hidden') {
        // Do not swap backgrounds if the page is not the active tab / minimized / etc
        return;
    }

    const elements = document.querySelectorAll('[data-swapbackground]');

    for (const element of elements) {


        const {children} = element;
        const activeIndex = Math.floor(Math.random() * children.length);

        for (let i = 0; i < children.length; i++) {
            const child = children[i];

            // instanceof check to make sure that typescript recognizes the dataset property
            // note: this will not work cross-realm (iframes)
            if (!(child instanceof HTMLElement)) {
                continue;
            }

            if (i === activeIndex) {
                child.dataset.swapbackgroundActive = '';
            }
            else {
                delete child.dataset.swapbackgroundActive;
            }
        }

    }
};

setInterval(swapBackgrounds, 10000)
