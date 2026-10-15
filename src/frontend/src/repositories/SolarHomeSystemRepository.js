import Client from "@/repositories/Client/AxiosClient.js"
import { resources } from "@/resources.js"

const resource = `/api/solar-home-systems`

export default {
  openPaygoManufacturers() {
    return Client.get(resources.openPaygoDevices.manufacturers)
  },
  registerOpenPaygoDevice(payload) {
    return Client.post(resources.openPaygoDevices.register, payload)
  },
  create(shs) {
    return Client.post(`${resource}`, shs)
  },
  detail(shsId) {
    return Client.get(`${resource}/${shsId}`)
  },
  transactions(shsId) {
    return Client.get(`${resource}/${shsId}/transactions`)
  },
  update(shsId, shs) {
    return Client.put(`${resource}/${shsId}`, shs)
  },
  delete(shsId) {
    return Client.delete(`${resource}/${shsId}`)
  },
  resource,
}
