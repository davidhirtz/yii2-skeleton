export default ($btn: HTMLButtonElement) => {
    $btn.addEventListener('click', () => document.body.classList.toggle('has-aside'));
}