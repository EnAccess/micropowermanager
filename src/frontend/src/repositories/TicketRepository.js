import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/tickets`

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
