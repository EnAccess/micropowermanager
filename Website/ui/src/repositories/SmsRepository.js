import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"
const resource = {
  list: `${baseUrl}/api/sms`,
  byPhone: `${baseUrl}/api/sms/phone`,
  search: `${baseUrl}/api/sms/search/`,
  groups: `${baseUrl}/api/connection-groups`,
  types: `${baseUrl}/api/connection-types`,
  send: `${baseUrl}/api/sms/storeandsend`,
  bulk: `${baseUrl}/api/sms/bulk`,
}

export default {
  list(param, personId) {
    if (personId) {
      return Client.get(`${resource.list}/${personId}`)
    } else {
      switch (param) {
        case "list":
          return Client.get(`${resource.list}`)
        case "groups":
          return Client.get(`${resource.groups}`)
        case "types":
          return Client.get(`${resource.types}`)
      }
    }
  },
  send(smsSend_PM, type) {
    switch (type) {
      case "bulk":
        return Client.post(`${resource.bulk}`, smsSend_PM)
      case "single":
        return Client.post(`${resource.send}`, smsSend_PM)
    }
  },
  detail(phone) {
    return Client.get(`${resource.byPhone}` + "/" + phone)
  },

  search(term) {
    return Client.get(`${resource.search}` + term)
  },
}
