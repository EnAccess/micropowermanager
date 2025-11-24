import Client from "@/repositories/Client/AxiosClient"

const resource = `/tickets/api/reports`

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
