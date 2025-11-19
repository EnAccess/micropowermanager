let routes = [
  {
    path: '/{{menu-item}}/{{submenu-item}}/page/:page_number',
    component: require('./plugins/textbee-sms-gateway/js/components/Component').default,
    meta: { layout: 'default' },
  },
]