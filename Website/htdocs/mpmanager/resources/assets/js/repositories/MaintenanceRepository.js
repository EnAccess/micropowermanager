import Client from './Client/AxiosClient'
const resource = {
    'list': '/api/maintenance',
    'create': '/api/maintenance/user',
}
export default {

    list () {
        return Client.get(`${resource.list}`)
    },
    create (personalData) {
        return Client.post(`${resource.create}`, personalData)
    }

}
