import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/micro-star-meters/micro-star-cert`

export default {
  post(cert) {
    return Client.post(`${resource}`, cert)
  },
  get() {
    return Client.get(`${resource}`)
  },
}
