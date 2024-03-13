let routes = [
    {
        path: '/viber-messaging/viber-overview',
        component:
            require('./plugins/viber-messaging/js/components/Overview/Overview')
                .default,
        meta: { layout: 'default' },
    },
]
