export const namespaced = true

export const state = {
  deviceTypes: [
    {
      type: "meter",
      display: "Meter",
    },
    {
      type: "solar_home_system",
      display: "Solar Home System",
    },
    {
      type: "e_bike",
      display: "E-Bike",
    },
    /*  Will be enabled in future
    {
            type: 'appliance',
            display: 'Appliance'
        }*/
  ],
}
export const getters = {
  getDeviceTypes: (state) => state.deviceTypes,
}
