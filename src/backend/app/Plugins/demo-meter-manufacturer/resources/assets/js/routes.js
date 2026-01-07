let routes = [
  {
    path: '/{{menu-item}}/{{submenu-item}}/page/:page_number',
    component: require('./plugins/demo-meter-manufacturer/js/components/Component').default,
    meta: { layout: 'default' },
  },
]