import Head from "next/head";
import { useEffect } from "react";
import { useRouter } from "next/router";
import { useAuth } from "../../contexts/AuthContext";
import ArticleForm, { type IArticleFormData } from "../../components/admin/ArticleForm";
import ArticlesList, { type IArticle } from "../../components/admin/ArticlesList";
import AdminNavigation from "../../components/admin/AdminNavigation";
import LoadingScreen from "../../components/common/LoadingScreen";
import useArticles from "../../hooks/useArticles";

const useAdminLogic = (): {
  user: { email: string } | null;
  loading: boolean;
  handleLogout: () => void;
} => {
  const { user, loading, logout } = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (!loading && !user) {
      void router.push("/login");
    }
  }, [user, loading, router]);

  const handleLogout = (): void => {
    void logout();
  };

  return { user, loading, handleLogout };
};

interface IArticlesContentProps {
  user: { email: string };
  handleLogout: () => void;
  articles: IArticle[];
  formData: IArticleFormData;
  setFormData: (data: IArticleFormData) => void;
  editingArticle: IArticle | null;
  formLoading: boolean;
  handleSubmit: (e: React.FormEvent) => void;
  handleEdit: (article: IArticle) => void;
  handleDelete: (id: number) => void;
  handleTogglePublish: (id: number, isPublished: boolean) => void;
}

const ArticlesContent = ({ 
  user, 
  handleLogout, 
  articles, 
  formData, 
  setFormData, 
  editingArticle, 
  formLoading, 
  handleSubmit, 
  handleEdit, 
  handleDelete, 
  handleTogglePublish 
}: IArticlesContentProps): JSX.Element => (
  <>
    <Head>
      <title>Articles Management - Admin</title>
    </Head>
    <div className="min-h-screen bg-gray-50">
      <AdminNavigation 
        user={user} 
        onLogout={handleLogout} 
        title="Articles Management"
        showBackLink={true}
      />
      <main className="py-6">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <ArticleForm
            formData={formData}
            setFormData={setFormData}
            onSubmit={handleSubmit}
            isEditing={!!editingArticle}
            loading={formLoading}
          />
          <ArticlesList
            articles={articles}
            onEdit={handleEdit}
            onDelete={handleDelete}
            onTogglePublish={handleTogglePublish}
          />
        </div>
      </main>
    </div>
  </>
);

const Articles = (): JSX.Element | null => {
  const { user, loading, handleLogout } = useAdminLogic();
  const {
    articles,
    formData,
    setFormData,
    editingArticle,
    loading: formLoading,
    handleSubmit,
    handleEdit,
    handleDelete,
    handleTogglePublish
  } = useArticles(user);

  const handleSubmitWrapper = (e: React.FormEvent): void => {
    void handleSubmit(e);
  };

  const handleDeleteWrapper = (id: number): void => {
    void handleDelete(id);
  };

  const handleTogglePublishWrapper = (id: number, isPublished: boolean): void => {
    void handleTogglePublish(id, isPublished);
  };

  if (loading) {
    return <LoadingScreen />;
  }

  if (!user) {
    return null;
  }

  return (
    <ArticlesContent
      user={user}
      handleLogout={handleLogout}
      articles={articles}
      formData={formData}
      setFormData={setFormData}
      editingArticle={editingArticle}
      formLoading={formLoading}
      handleSubmit={handleSubmitWrapper}
      handleEdit={handleEdit}
      handleDelete={handleDeleteWrapper}
      handleTogglePublish={handleTogglePublishWrapper}
    />
  );
};

export default Articles;