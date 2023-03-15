document.addEventListener('DOMContentLoaded', function()
{
    if(document.querySelector(".wsklad-toc"))
    {
        tocbot.init({
            tocSelector: '.wsklad-toc',
            contentSelector: '.wsklad-toc-container',
            headingSelector: 'h1, h2, h3, h4, h5',
            hasInnerContainers: true,
            listClass: 'list-group m-0',
            linkClass: 'stretched-link',
            listItemClass: 'list-group-item',
            activeListItemClass: 'active',
            headingsOffset: 55,
            scrollSmoothOffset: -55,
            positionFixedSelector: '.wsklad-sidebar-toc',
            positionFixedClass: 'is-position-fixed position-sticky',
        });
    }
});