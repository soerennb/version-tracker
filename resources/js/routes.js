import TimelineView from './views/TimelineView.vue';
import ProductDetailView from './views/ProductDetailView.vue';
import ProductsView from './views/ProductsView.vue';
import ReleaseDetailView from './views/ReleaseDetailView.vue';
import CompareView from './views/CompareView.vue';

export default [
    {
        path: '/',
        redirect: '/products',
    },
    {
        path: '/products',
        name: 'products',
        component: ProductsView,
        meta: {
            title: 'products.title',
        },
    },
    {
        path: '/products/:id',
        name: 'product',
        component: ProductDetailView,
        meta: {
            title: 'product.title',
        },
    },
    {
        path: '/timeline',
        name: 'timeline',
        component: TimelineView,
        meta: {
            title: 'timeline.title',
        },
    },
    {
        path: '/releases/:id',
        name: 'release',
        component: ReleaseDetailView,
        meta: {
            title: 'release.title',
        },
    },
    {
        path: '/products/:productId/compare',
        name: 'compare',
        component: CompareView,
        meta: {
            title: 'compare.title',
        },
    },
];
