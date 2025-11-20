import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/swifta-payment/authentication`

export default {
  get() {
    return Client.get(`${resource}`)
  },
}
