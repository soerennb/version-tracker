import HomeView from './views/HomeView.vue';
import TimelineView from './views/TimelineView.vue';
import ProductDetailView from './views/ProductDetailView.vue';
import ProductsView from './views/ProductsView.vue';
import ReleaseDetailView from './views/ReleaseDetailView.vue';
import CompareView from './views/CompareView.vue';
import SecurityView from './views/SecurityView.vue';
import SecurityDetailView from './views/SecurityDetailView.vue';
import SearchView from './views/SearchView.vue';
import NotFoundView from './views/NotFoundView.vue';
import AccountLoginView from './views/AccountLoginView.vue';
import AccountRegisterView from './views/AccountRegisterView.vue';
import AccountVerifyView from './views/AccountVerifyView.vue';
import AccountForgotPasswordView from './views/AccountForgotPasswordView.vue';
import AccountResetPasswordView from './views/AccountResetPasswordView.vue';
import AccountInvitationView from './views/AccountInvitationView.vue';
import AccountView from './views/AccountView.vue';

export default [
    {
        path: '/',
        name: 'home',
        component: HomeView,
        meta: {
            title: 'home.title',
        },
    },
    {
        path: '/products',
        name: 'products',
        component: ProductsView,
        meta: {
            title: 'products.title',
            feature: 'products',
        },
    },
    {
        path: '/account/login',
        name: 'account-login',
        component: AccountLoginView,
        meta: { title: 'account.login' },
    },
    {
        path: '/account/register',
        name: 'account-register',
        component: AccountRegisterView,
        meta: { title: 'account.register' },
    },
    {
        path: '/account/verify',
        name: 'account-verify',
        component: AccountVerifyView,
        meta: { title: 'account.verify' },
    },
    {
        path: '/account/forgot-password',
        name: 'account-forgot-password',
        component: AccountForgotPasswordView,
        meta: { title: 'account.forgotPassword' },
    },
    {
        path: '/account/reset-password',
        name: 'account-reset-password',
        component: AccountResetPasswordView,
        meta: { title: 'account.resetPassword' },
    },
    {
        path: '/account/invitation',
        name: 'account-invitation',
        component: AccountInvitationView,
        meta: { title: 'account.invitation' },
    },
    {
        path: '/account',
        name: 'account',
        component: AccountView,
        meta: { title: 'account.title' },
    },
    {
        path: '/products/:id',
        name: 'product',
        component: ProductDetailView,
        meta: {
            title: 'product.title',
            feature: 'products',
        },
    },
    {
        path: '/timeline',
        name: 'timeline',
        component: TimelineView,
        meta: {
            title: 'timeline.title',
            feature: 'timeline',
        },
    },
    {
        path: '/security',
        name: 'security',
        component: SecurityView,
        meta: {
            title: 'security.title',
            feature: 'security',
        },
    },
    {
        path: '/security/:id',
        name: 'security-detail',
        component: SecurityDetailView,
        meta: {
            title: 'security.detailTitle',
            feature: 'security',
        },
    },
    {
        path: '/search',
        name: 'search',
        component: SearchView,
        meta: {
            title: 'search.title',
            feature: 'search',
        },
    },
    {
        path: '/releases/:id',
        name: 'release',
        component: ReleaseDetailView,
        meta: {
            title: 'release.title',
            feature: 'catalog',
        },
    },
    {
        path: '/products/:productId/compare',
        name: 'compare',
        component: CompareView,
        meta: {
            title: 'compare.title',
            feature: 'compare',
        },
    },
    {
        path: '/:pathMatch(.*)*',
        name: 'not-found',
        component: NotFoundView,
        meta: {
            title: 'notFound.title',
        },
    },
];
