let routes = [
  {
    path: '/{{menu-item}}/{{submenu-item}}/page/:page_number',
    component: require('./plugins/swifta-payment-provider/js/components/Component').default,
    meta: { layout: 'default' },
  },
]