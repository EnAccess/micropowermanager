import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/tickets/api/tickets/comments`

export default {
  create(commentPm) {
    return Client.post(`${resource}`, commentPm)
  },
}
