import Client from './Client/AxiosClient'
const resource ='/tickets/api/export'
export default{

    list(){
        return Client.get(`${resource}`)
    },
    download(id,reference){
        return `${resource}/download/`+`${id}`+`${reference}`
    }

}
