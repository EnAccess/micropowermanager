import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/wavecom/upload`

export default {
  post(transactionFile) {
    return Client.post(`${resource}`, transactionFile)
  },
}
