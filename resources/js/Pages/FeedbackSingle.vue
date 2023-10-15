<script setup lang="ts">
import Modal from "@/Components/Modal.vue";
import Layout from "@/Layouts/Layout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { ref, onMounted, onBeforeUnmount, reactive, computed } from "vue";
import axios from "axios";
import { usePage } from "@inertiajs/vue3";
import ClassicEditor from "@ckeditor/ckeditor5-build-classic";
import Swal from "sweetalert2";
window.appChannel.bind("voted", function (data) {
  console.log(data);
});
window.appChannel.bind("comment-create-or-update", function (data) {
  console.log(data);
  if(data.created){
    let comment = data.comment;
    comment.username = data.creator;
    pageData.items.splice(0,0, comment);
  }else{
    // TODO: if updated update the comment but this is not used
  }
});


const page = usePage();
const editorConfig = {
  toolbar: ["heading", "|", "bold", "italic", "link", "bulletedList", "numberedList", "blockQuote"],
  heading: {
    options: [
      { model: "paragraph", title: "Paragraph", class: "ck-heading_paragraph" },
      { model: "heading1", view: "h1", title: "Heading 1", class: "ck-heading_heading1" },
      { model: "heading2", view: "h2", title: "Heading 2", class: "ck-heading_heading2" },
    ],
  },
};
const user = computed(() => page.props.auth.user);
const feedback = computed(() => page.props.feedback);
const feedbackId = computed(() => page.props.feedback_id);
const apiMeta = ref({});
const loading = ref(false);
const pageData = reactive({
  feedback: {
    comment: "",
    feedback_id: feedbackId,
  },
  modal: {
    show: false,
    feedback: null,
    feedbackIndex: null,
  },
  items: [],
  pageNo: 1,
  loading: false,
  searchTerm: "",
});
const search = () => {
  pageData.pageNo = 1;
  pageData.items = [];
  getPageData();
};
const getPageData = () => {
  return new Promise((resolve, reject) => {
    loading.value = true;
    fetch(`/api/feedback/${feedbackId.value}/comments?page=${pageData.pageNo}&search=${pageData.searchTerm}`).then((response) =>
      response.json().then((response) => {
        console.log(response);
        const data = response;
        pageData.items = pageData.items.concat(data.data);
        delete data.items;
        apiMeta.value = data;
        loading.value = false;
        resolve(true);
      })
    );
  });
};
const infiniteScrollTrigger = ref();
// Add scroll event listener
const handleScroll = async () => {
  const trigger = infiniteScrollTrigger.value;
  if (trigger) {
    // debugger;
    const rect = trigger.getBoundingClientRect();

    const isInView = rect.top >= 0 && rect.bottom <= (window.innerHeight || document.documentElement.clientHeight);

    if (isInView) {
      await loadMoreItems();
    } else {
      console.log("not in view");
    }
  } else {
    // console.log("trigger not visible");
  }
};

onMounted(() => {
  window.addEventListener("scroll", handleScroll);
  getPageData().then(() => {
    handleScroll();
  });
});

onBeforeUnmount(() => {
  window.removeEventListener("scroll", handleScroll);
});

const loadMoreItems = () => {
  // Call your API or method to load more items
  // Update items.value with the new data
  if (apiMeta.value.next_page_url) {
    pageData.pageNo += 1;
    return getPageData();
  } else {
  }
};
const statusToText = {
  0: "Pending",
  1: "In Progress",
  2: "Completed",
};
const getStatusColorClass = (status) => {
  switch (status) {
    case 0:
      return "text-red-500"; // Tailwind CSS class for red text
    case 1:
      return "text-blue-500"; // Tailwind CSS class for blue text
    case 2:
      return "text-green-500"; // Tailwind CSS class for green text
    default:
      return "text-black"; // Default color if status is not recognized
  }
};

function addComment() {
  if(user.value){
    let config = {
    method: "post",
    maxBodyLength: Infinity,
    url: "http://127.0.0.1:8000/api/comments",
    headers: {
      "Content-Type": "application/json",
    },
    data: JSON.stringify(pageData.feedback),
  };
  pageData.loading = true;
  axios
    .request(config)
    .then(function (json) {
      console.log(json);
      // pageData.items.splice(0, 0, {
      //   comment: pageData.feedback.comment,
      //   username: "You",
      // });
      pageData.feedback.comment = "";
    })
    .finally(() => {
      pageData.loading = false;
    });

  }else{
    Swal.fire("Please Register to comment");
  }
}
function vote(type = "upvote", feedback) {
  fetch(`/api/feedback/${feedback.id}/${type}`, {
    headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
  })
    .then((response) =>
      response.json().then((res) => {
        if (response.status == 200) {
          feedback.upvotes = res.feedback.upvotes;
          feedback.downvotes = res.feedback.downvotes;
          feedback.vote_type = +(type == "upvote");
        } else {
          alert(res.message);
        }
      })
    )
    .catch((err) => console.log(err));
}
</script>

<style >
input {
  border: 1px solid #ccc;
  border-radius: 0;
}

button {
  border-radius: 0;
}

.infinite-scroll-trigger {
  height: 50px;
  /* Adjust this height as needed */
  background-color: #f5f5f5;
  /* Adjust the color as needed */
}

/* Add this CSS in your styles file or in your HTML file if you're not using an external stylesheet */
@keyframes spin {
  0% {
    transform: rotate(0deg);
  }

  100% {
    transform: rotate(360deg);
  }
}

.loader {
  border: 4px solid rgba(255, 255, 255, 0.3);
  border-top: 4px solid #000;
  border-radius: 50%;
  width: 36px;
  height: 36px;
  animation: spin 1s linear infinite;
}
.ck-editor[role="application"] {
  width: 100%!important;
}
</style>

<template>
  <Layout>
    <template #header>
      <h2>Feedback #{{ feedbackId }}</h2>
    </template>
    <div class="row p-8 w-full">
      <div class="bg-white shadow p-4 rounded mb-4">
        <div class="flex justify-between">
          <div>
            <h2 class="text-xl font-bold text-gray-800">{{ feedback.title }}</h2>
            <p class="text-sm text-gray-600">{{ feedback.description }}</p>
            <p class="text-sm font-semibold mt-2">{{ feedback.category }}</p>
            <div class="flex mt-4">
              <div class="mr-4">
                <p class="text-lg font-bold text-green-500">{{ feedback.upvotes }}</p>
                <p class="text-xs text-gray-400">Upvotes</p>
              </div>
              <div>
                <p class="text-lg font-bold text-red-500">{{ feedback.downvotes }}</p>
                <p class="text-xs text-gray-400">Downvotes</p>
              </div>
            </div>
          </div>
          <div class="w-1/6" v-if="$page.props.auth.user">
            <div class="flex flex-col items-end gap-3 pr-3 py-3">
              <button @click="vote('upvote', feedback)" :disabled="pageData.loading || feedback.vote_type == 1">
                <svg
                  :class="{ 'text-green-600': !(feedback.vote_type == 1), 'text-gray-400': feedback.vote_type == 1 }"
                  class="w-6 h-6"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke-width="5"
                  stroke="currentColor"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                </svg>
              </button>
              <span class="p-1">{{ feedback.upvotes }}</span>
              <span class="p-1">{{ feedback.downvotes }}</span>
              <button @click="vote('downvote', feedback)" :disabled="pageData.loading || feedback.vote_type == 0">
                <svg
                  :class="{ 'text-red-600': !(feedback.vote_type == 0), 'text-gray-400': feedback.vote_type == 0 }"
                  class="w-6 h-6"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke-width="5"
                  stroke="currentColor"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white shadow p-4 rounded mb-4">
        <h2 class="text-xl font-bold text-gray-800 mb-2">Add Comment</h2>

        <div class="flex gap-3 items-end">
          <!--  -->
          <ckeditor :editor="ClassicEditor" v-model="pageData.feedback.comment" :config="editorConfig" style="width: 100%"> </ckeditor>

          <div>
            <div class="loader" v-if="pageData.loading"></div>
            <button v-else @click="addComment" class="mb-2 mt-2 bg-blue-500 text-white px-4 py-2 border rounded-xl">Submit</button>
          </div>
        </div>
        <!-- <pre>{{pageData.items}}</pre> -->
        <h2 class="text-xl font-bold text-gray-800 mt-5">Comments</h2>

        <div v-for="comment in pageData.items" :key="comment.id" class="flex content mb-3 mt-3 shadow-lg" style="min-height: 5em">
          <font-awesome icon="fa fa-person" class="w-16 h-16 object-cover rounded-full mr-4" />
          <!-- <img :src="comment.userProfilePicture" alt="Profile Picture" class="w-10 h-10 object-cover rounded-full mr-4" /> -->
          <div>
            <p class="text-gray-800 font-semibold">{{ comment.username }}</p>
            <div class="text-sm bg-white" v-html="comment.comment"></div>
          </div>
        </div>
        <!-- Loading Row -->
        <div class="flex justify-center items-center mt-8 mb-8">
          <h2 v-if="apiMeta.current_page && !apiMeta.next_page_url">No More Items</h2>
          <div class="infinite-scroll-trigger" ref="infiniteScrollTrigger" v-else-if="!loading"></div>
          <div class="loader" v-else></div>
        </div>
      </div>
    </div>
  </Layout>
</template>
