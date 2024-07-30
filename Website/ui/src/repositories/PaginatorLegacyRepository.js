import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

export default {
  get(url, params) {
    // when the consumer passes a fully qualified url use it,
    // otherwise use the base url from axiosClient
    let resource = url.startsWith("http") ? url : `${baseUrl}${url}`
    return Client.get(`${resource}`, { params: params })
  },
  post(url, postData) {
    // when the consumer passes a fully qualified url use it,
    // otherwise use the base url from axiosClient
    let resource = url.startsWith("http") ? url : `${baseUrl}${url}`
    return Client.post(`${resource}`, postData)
  },
}
