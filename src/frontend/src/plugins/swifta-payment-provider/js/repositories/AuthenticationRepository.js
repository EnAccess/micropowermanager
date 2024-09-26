import { baseUrl } from "@/repositories/Client/AxiosClient"
import Client from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/swifta-payment/authentication`

export default {
  get() {
    return Client.get(`${resource}`)
  },
}
