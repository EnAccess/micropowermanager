import Client from "@/repositories/Client/AxiosClient"

export default {
  get(resource, params) {
    return Client.get(`${resource}`, { params: params })
  },
  post(resource, postData) {
    return Client.post(`${resource}`, postData)
  },
}
