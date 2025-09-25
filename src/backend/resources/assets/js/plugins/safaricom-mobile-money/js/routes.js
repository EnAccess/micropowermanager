let routes = [
  {
    path: '/{{menu-item}}/{{submenu-item}}/page/:page_number',
    component: require('./plugins/safaricom-mobile-money/js/components/Component').default,
    meta: { layout: 'default' },
  },
]