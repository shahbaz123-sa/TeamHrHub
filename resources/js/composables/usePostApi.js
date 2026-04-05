export const usePostApi = () => {
  const accessToken = useCookie("accessToken").value;

  const fetchPosts = async (filters = {}) => {
    try {
      const { data, error } = await useApi(
        createUrl("/posts", {
          query: {
            ...filters,
            page: filters.page || 1,
            per_page: filters.per_page || 10,
            sort_by: filters.sort_by || "id",
            order_by: filters.order_by || "desc",
          },
        })
      );

      if (error.value) throw error.value;
      return data.value;
    } catch (error) {
      console.error("Failed to fetch posts", error);
      throw error;
    }
  };

  const getPost = async (id) => {
    try {
      const { data, error } = await useApi(`/posts/${id}`);

      if (error.value) throw error.value;
      return data.value?.data;
    } catch (error) {
      console.error("Failed to fetch post", error);
      throw error;
    }
  };

  const getPostBySlug = async (slug) => {
    try {
      const { data, error } = await useApi(`/post/slug/${slug}`);

      if (error.value) throw error.value;
      return data.value?.data;
    } catch (error) {
      console.error("Failed to fetch post by slug", error);
      throw error;
    }
  };

  const createPost = async (postData) => {
    try {
      const { data } = await $api("/posts", {
        method: "POST",
        body: postData,
        headers: {
          Authorization: `Bearer ${accessToken}`,
        },
      });

      return data.value?.data;
    } catch (error) {
      console.error("Failed to create post", error);
      throw error;
    }
  };

  const updatePost = async (id, postData) => {
    try {
      postData.append("_method", "PUT");

      const { data } = await $api(`/posts/${id}`, {
        method: "POST",
        body: postData,
        headers: {
          Authorization: `Bearer ${accessToken}`,
        },
      });

      return data.value?.data;
    } catch (error) {
      console.error("Failed to update post", error);
      throw error;
    }
  };

  const deletePost = async (id) => {
    try {
      await $api(`/posts/${id}`, {
        method: "DELETE",
        headers: {
          Authorization: `Bearer ${accessToken}`,
        },
      });

      return true;
    } catch (error) {
      console.error("Failed to delete post", error);
      throw error;
    }
  };

  return {
    fetchPosts,
    getPost,
    getPostBySlug,
    createPost,
    updatePost,
    deletePost,
  };
};
