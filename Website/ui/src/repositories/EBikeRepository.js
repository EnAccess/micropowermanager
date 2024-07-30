import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/e-bikes`

export default {
  create(eBike) {
    return Client.post(`${resource}`, eBike)
  },
  detail(serialNumber) {
    return Client.get(`${resource}/${serialNumber}`)
  },
  update(eBikeId, eBike) {
    return Client.put(`${resource}/${eBikeId}`, eBike)
  },
  delete(eBikeId) {
    return Client.delete(`${resource}/${eBikeId}`)
  },
  switch(postData) {
    return Client.post(`${resource}/switch`, postData)
  },
  resource,
}
