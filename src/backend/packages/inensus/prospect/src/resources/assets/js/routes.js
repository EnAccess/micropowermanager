let routes = [
  {
    path: '/{{menu-item}}/{{submenu-item}}/page/:page_number',
    component: require('./plugins/prospect/js/components/Component').default,
    meta: { layout: 'default' },
  },
]