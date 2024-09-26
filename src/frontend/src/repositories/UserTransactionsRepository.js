import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/people`

export default {
  list(userId, page) {
    return Client.get(`${resource}/${userId}/transactions?page=${page}`)
  },
}
