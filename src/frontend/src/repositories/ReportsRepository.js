import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/reports`

export default {
  list(type) {
    return Client.get(`${resource}?type=` + type)
  },
  download(id) {
    return Client.get(`${resource}/download/${id}`, {
      responseType: "blob",
    })
  },
}
