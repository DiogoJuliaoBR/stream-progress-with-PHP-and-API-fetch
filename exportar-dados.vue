<template>
  <div style="user-select: text">
    <q-modal v-model="modal_show_progress" minimized ref="modalRef" no-backdrop-dismiss>
      <div class="modal-progress">
        <div><b>PROGRESSO DA SOLICITAÇÃO</b></div>
        <div style="margin-top: 12px" v-if="data_from_server!=null">
          {{ data_from_server.data }}
          <p>
            Processados: {{data_from_server.index}}/{{ total_data }}<br />
            Ignorados: {{data_from_server.error}}
          </p>
          <div style="text-align: right; margin-top: 12px;">
            <q-btn color="indigo-6" label="Fechar" :disable="fechar_disable" @click="resetValuesProgress()" />
          </div>
        </div>
        <div style="margin-top: 12px" v-else>
          <div style="padding: 6px">
          Este processo pode demorar até 10 min e não poderá ser interrompido!
          <p style="margin-top: 12px;"><b>Tem certeza que deseja continuar?</b></p>
          </div>
          <div style="text-align: right; margin-top: 12px;">
            <q-btn v-if="!loading_data" color="red" v-close-overlay label="Cancelar" @click="params=null" />
            <q-btn color="indigo-6" label="Gerar planilha" :loading="loading_data" @click="gerarPlanilha(params)" style="margin-left: 12px;" />
          </div>
        </div>
      </div>
    </q-modal>
  </div>
</template>

<script>
export default {
  data () {
    return {
      modal_show_progress: false,
      loading_data: false,
      data_from_server: null,
      params: null,
      total_data: 0,
      fechar_disable: true,
    }
  },
  methods: {
    resetValuesProgress() {
      this.params=null
      this.modal_show_progress=false
      this.data_from_server=null
      this.total_data=0      
    },
    async gerarPlanilhaConfirm (params) {
      this.modal_show_progress=true
      this.params=params 
    },

    /**
     * Referências:
     * Utilizando STREAM API com fetch
     * https://flaviocopes.com/stream-api/
     * 
     * No back end com echo str_pad('', 4096) . "\n";
     * https://www.codegrepper.com/code-examples/php/header%7B%22Content-type%3Atext%2Fevent-stream%22%7D
     * 
     * Deixando o código mais limpo
     * https://javascript.info/fetch-progress
     */
  
    async gerarPlanilha() {
      this.fechar_disable=true
      this.loading_data=true
      let params = new FormData();
      params.append('params', this.params)
      const fetchedResource = await fetch(`${url}painel-adm/gerar-csv`, {
        method: 'POST',
        body: params,
        headers: {
          'Authorization': `Bearer ${token}`
        }
      })
      const reader = await fetchedResource.body.getReader()
      const decoder = new TextDecoder()
      let chunks = new Array;

      while(true) {
        
        const {done, value} = await reader.read();
        if (done) {
          this.loading_data=false
          this.getFileCsvList()
          this.$q.notify({
            position: 'top',
            message: `O arquivo foi gerado com sucesso!`,
            type: "positive",
            timeout: 3000,
          });
          this.fechar_disable=false
          this.modal_show_progress=false
          this.resetValuesProgress()
          break
        }
        
        try {
          chunks.push(JSON.parse(decoder.decode(value)))
          if(chunks.length==1) {
            this.total_data=chunks[0].total            
          }
          this.data_from_server=JSON.parse(decoder.decode(value))
        } catch (error) {
          // do nothing
        } 
      }
    }
  }
}
</script>

<style scoped>

.modal-progress {
  padding: 16px;
  width: 600px;
}

@media screen and (max-width: 600px) {
  .modal-progress {
    width: 500px;
  }
}
</style>
