import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/tickets/api/reports`

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
