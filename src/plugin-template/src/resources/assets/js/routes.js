let routes = [
  {
    path: '/{{menu-item}}/{{submenu-item}}/page/:page_number',
    component: require('./plugins/{{package-name}}/js/components/Component').default,
    meta: { layout: 'default' },
  },
]