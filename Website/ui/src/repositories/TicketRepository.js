import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/tickets/api`

export default {
  listCategory() {
    return Client.get(`${resource}/labels`)
  },

  create(maintenanceData) {
    return Client.post(`${resource}/ticket`, maintenanceData)
  },
  close(id) {
    return Client.delete(`${resource}/ticket/${id}`)
  },
}
