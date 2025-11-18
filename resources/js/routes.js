import TimelineView from './views/TimelineView.vue';

export default [
    {
        path: '/',
        redirect: '/timeline',
    },
    {
        path: '/timeline',
        name: 'timeline',
        component: TimelineView,
        meta: {
            title: 'timeline.title',
        },
    },
];
