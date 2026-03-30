import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/swifta-payment/authentication`

export default {
  get() {
    return Client.get(`${resource}`)
  },
}
