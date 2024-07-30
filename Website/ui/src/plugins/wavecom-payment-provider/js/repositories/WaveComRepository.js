import { baseUrl } from "@/repositories/Client/AxiosClient"
import Client from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/wavecom/upload`

export default {
  post(transactionFile) {
    return Client.post(`${resource}`, transactionFile)
  },
}
