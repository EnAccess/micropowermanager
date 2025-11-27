import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/tickets/reports`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  download(id) {
    return Client.get(`${resource}/download/${id}`, {
      responseType: "blob",
    })
  },
}
