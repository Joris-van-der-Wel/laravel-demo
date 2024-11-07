// attach the listener on document so it works for dynamically created elements
document.addEventListener('click', (event) => {
    const {target} = event;

    // instanceof check to make sure that typescript recognizes the proper properties
    // note: this will not work cross-realm (iframes)
    if (target instanceof HTMLAnchorElement && 'copyLink' in target.dataset) {
        navigator.clipboard.writeText(target.href).then(() => {
            // todo: display a message to the user that the copy was successful
        });
        event.preventDefault();
    }
});
