let routes = [
  {
    path: '/{{menu-item}}/{{submenu-item}}/page/:page_number',
    component: require('./plugins/demo-shs-manufacturer/js/components/Component').default,
    meta: { layout: 'default' },
  },
]