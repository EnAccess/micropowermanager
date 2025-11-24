import Client from "@/repositories/Client/AxiosClient"

const resource = {
  list: `/api/sms`,
  byPhone: `/api/sms/phone`,
  search: `/api/sms/search/`,
  groups: `/api/connection-groups`,
  types: `/api/connection-types`,
  send: `/api/sms/storeandsend`,
  bulk: `/api/sms/bulk`,
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
